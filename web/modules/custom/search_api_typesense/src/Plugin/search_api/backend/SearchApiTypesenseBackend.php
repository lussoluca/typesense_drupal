<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Plugin\search_api\backend;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_typesense\Api\Config;
use Drupal\search_api_typesense\Api\SearchApiTypesenseException;
use Drupal\search_api_typesense\Api\TypesenseClient;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchApiTypesenseBackend.
 *
 * @SearchApiTypesenseBackend.
 *
 * @SearchApiBackend(
 *   id = "search_api_typesense",
 *   label = @Translation("Search API Typesense"),
 *   description = @Translation("Index items using Typesense server.")
 * )
 */
class SearchApiTypesenseBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The server corresponding to this backend.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The set of Search API indexes on this server.
   *
   * @var array
   */
  protected array $indexes;

  /**
   * The set of Typesense collections on this server.
   *
   * @var array
   */
  protected array $collections;

  /**
   * @var \Drupal\search_api_typesense\Api\TypesenseClientInterface|null
   */
  private ?TypesenseClientInterface $typesense = NULL;

  /**
   * Constructs a Typesense backend plugin.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   A plugin definition.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger interface.
   * @param \Drupal\search_api\Utility\FieldsHelper|null $fieldsHelper
   *   The fields helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface|null $messenger
   *   The messenger.
   *
   * @throws \Http\Client\Exception
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected $logger,
    protected $fieldsHelper,
    private readonly ConfigFactoryInterface $configFactory,
    protected $messenger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Don't try to get indexes from server that is not created yet.
    if ($this->server == NULL) {
      return;
    }
    $this->server = $this->getServer();
    $this->indexes = $this->server->getIndexes();

    $config = $this->getClientConfiguration(FALSE);
    if ($config == NULL || !$config->valid()) {
      return;
    }

    try {
      $this->typesense = new TypesenseClient($this->getClientConfiguration(FALSE));
      $this->collections = $this->typesense->retrieveCollections();
      $this->syncIndexesAndCollections();
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to retrieve server and/or index information.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.search_api_typesense'),
      $container->get('search_api.fields_helper'),
      $container->get('config.factory'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo: include only collections that have a corresponding Search API index.
   */
  public function viewSettings(): array {
    $info = [];

    try {
      // Loop over indexes as it's possible for an index to not yet have a
      // corresponding collection.
      $num = 1;
      foreach ($this->indexes as $index) {
        $collection = $this->typesense->retrieveCollection($index->getProcessor('typesense_schema')->getConfiguration()['schema']['name']);

        $info[] = [
          'label' => $this->t('Typesense collection @num: name', [
            '@num' => $num,
          ]),
          'info' => $index->getProcessor('typesense_schema')->getConfiguration()['schema']['name'],
        ];

        $collection_created = [
          'label' => $this->t('Typesense collection @num: created', [
            '@num' => $num,
          ]),
          'info' => NULL,
        ];

        $collection_documents = [
          'label' => $this->t('Typesense collection @num: documents', [
            '@num' => $num,
          ]),
          'info' => NULL,
        ];

        if ($collection != NULL) {
          $collection_created['info'] = date(DATE_ISO8601, $collection->retrieve()['created_at']);
          $collection_documents['info'] = $collection->retrieve()['num_documents'] > '0'
            ? number_format($collection->retrieve()['num_documents'])
            : $this->t('no documents have been indexed');
        }
        else {
          $collection_created['info'] = $this->t('Collection not yet created. Add one or more fields to the index and configure the Typesense Schema processor to create the collection.');
        }

        $info[] = $collection_created;
        $info[] = $collection_documents;

        $num++;
      }

      $server_health = $this->typesense->retrieveHealth();

      $info[] = [
        'label' => $this->t('Typesense server health'),
        'info' => $server_health['ok'] ? 'ok' : 'not ok',
      ];

      $server_debug = $this->typesense->retrieveDebug();

      $info[] = [
        'label' => $this->t('Typesense server version'),
        'info' => $server_debug['version'],
      ];

      return $info;
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to retrieve server and/or index information.'));
    }

    return [];
  }

  /**
   * Returns Typesense auth credentials iff ALL needed values are set.
   */
  protected function getClientConfiguration($read_only = TRUE): ?Config {
    $api_key_key = $read_only ? 'ro_api_key' : 'rw_api_key';

    $config = $this->configFactory->get('search_api.server.' . $this->server->id())->get('backend_config');

    if (isset($config[$api_key_key], $config['nodes'], $config['retry_interval_seconds'])) {
      return new Config(
        api_key: $config[$api_key_key],
        nodes: array_filter($config['nodes'], function ($key) {
          return is_numeric($key);
        }, ARRAY_FILTER_USE_KEY),
        retry_interval_seconds: intval($config['retry_interval_seconds']),
      );
    }

    return NULL;
  }

  /**
   * Returns a Typesense schemas.
   *
   * @return array
   *   A Typesense schema array or [].
   */
  protected function getSchema($collection_name): array {
    $typesense_schema_processor = $this->indexes[$collection_name]->getProcessor('typesense_schema');

    return $typesense_schema_processor->getTypesenseSchema();
  }

  /**
   * Synchronizes Typesense collection schemas with Search API indexes.
   *
   * When Search API indexes are created, there's not enough information to
   * create the corresponding collection (Typesense requires the full schema
   * to create a collection, and no fields will be defined yet when the Search
   * API index is created).
   *
   * Here, we make sure that there's an existing collection for every index.
   *
   * We don't need to verify the processor and collection fields match since
   * the index will be marked in need of reindexing when the processor changes.
   *
   * Reindexing a Typesense collection always involves recreating it and doing
   * the indexing from scratch.
   *
   * We handle all this here instead of in the class's addIndex() method because
   * the index's fields and Typesense Schema processor must already be
   * configured before the collection can be created in the first place.
   */
  protected function syncIndexesAndCollections(): void {
    try {
      // If there are no indexes, we have nothing to do.
      if (count($this->indexes) == 0) {
        return;
      }

      // Loop over as many indexes as we have.
      foreach ($this->indexes as $index) {
        // Get the defined schema from the processor.
        $typesense_schema = $this->getSchema($index->id());

        // If this index has no Typesense-specific properties defined in the
        // typesense_schema processor, there's nothing we CAN do here.
        //
        // Typesense has made the default_sorting_field setting optional, in
        // v0.20.0, so all we can really do is check for fields.
        if (count($typesense_schema['fields']) == 0) {
          return;
        }

        // Check to see if the collection corresponding to this index exists.
        $collection = $this->typesense->retrieveCollection($typesense_schema['name']);

        // If it doesn't, create it.
        if ($collection == NULL) {
          $this->typesense->createCollection($typesense_schema);
        }
      }
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to sync Search API index schema and Typesense schema.'));
    }
  }

  /**
   * Provides Typesense Server settings.
   *
   * @todo: Adding new nodes by AJAX is broken, so:
   *   - unbreak it,
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;

    $num_nodes = $form_state->get('num_nodes');

    if ($num_nodes === NULL) {
      $node_field = $form_state->set('num_nodes', 1);
      $num_nodes = 1;
    }

    $form['ro_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read-only API key'),
      '#maxlength' => 128,
      '#size' => 30,
      '#required' => TRUE,
      '#description' => $this->t('A read-only API key for this Typesense instance. Read-only keys are safe for use in front-end applications where they will be transmitted to the client.'),
      '#default_value' => $this->configuration['ro_api_key'] ?? NULL,
      '#attributes' => [
        'placeholder' => 'ro_1234567890',
      ],
    ];

    $form['rw_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read-write API key'),
      '#maxlength' => 128,
      '#size' => 30,
      '#required' => TRUE,
      '#description' => $this->t('A read-write API key for this Typesense instance. Required for indexing content. <strong>This key must be kept secret and never trasmitted to the client. Ideally, it will be provided by an environment variable and never stored in version control systems</strong>.'),
      '#default_value' => $this->configuration['rw_api_key'] ?? NULL,
      '#attributes' => [
        'placeholder' => 'rw_1234567890',
      ],
    ];

    $form['nodes'] = [
      '#type' => 'container',
      '#title' => $this->t('Nodes'),
      '#description' => $this->t('The Typesense server node(s).'),
      '#attributes' => [
        'id' => 'nodes-fieldset-wrapper',
      ],
    ];

    for ($i = 0; $i < $num_nodes; $i++) {
      $form['nodes'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Node @num', ['@num' => $i + 1]),
        '#open' => $num_nodes === 1 && $i === 0,
      ];

      $form['nodes'][$i]['host'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Host'),
        '#maxlength' => 128,
        '#required' => TRUE,
        '#description' => $this->t('The hostname for connecting to this node.'),
        '#default_value' => $this->configuration['nodes'][$i]['host'] ?? NULL,
        '#attributes' => [
          'placeholder' => 'typesense.example.com',
        ],
      ];

      $form['nodes'][$i]['port'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Port'),
        '#maxlength' => 5,
        '#required' => TRUE,
        '#description' => $this->t('The port for connecting to this node.'),
        '#default_value' => $this->configuration['nodes'][$i]['port'] ?? NULL,
        '#attributes' => [
          'placeholder' => '576',
        ],
      ];

      $form['nodes'][$i]['protocol'] = [
        '#type' => 'select',
        '#title' => $this->t('Protocol'),
        '#options' => [
          'http' => 'http',
          'https' => 'https',
        ],
        '#description' => $this->t('The protocol for connecting to this node.'),
        '#default_value' => $this->configuration['nodes'][$i]['protocol'] ?? 'https',
      ];
    }

    $form['nodes']['actions'] = [
      '#type' => 'actions',
    ];

    $form['nodes']['actions']['add_node'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another node'),
      '#name' => 'add_node',
      '#submit' => [[$this, 'addNode']],
      '#ajax' => [
        'callback' => [$this, 'addNodeCallback'],
        'wrapper' => 'nodes-fieldset-wrapper',
      ],
    ];

    if ($num_nodes > 1) {
      $form['nodes']['actions']['remove_node'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove node'),
        '#name' => 'remove_node',
        '#submit' => [[$this, 'removeNode']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$this, 'addNodeCallback'],
          'wrapper' => 'nodes-fieldset-wrapper',
        ],
      ];
    }

    $form['retry_interval_seconds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Connection timeout (seconds)'),
      '#maxlength' => 2,
      '#size' => 10,
      '#required' => TRUE,
      '#description' => $this->t('Time to wait before timing-out the connection attempt.'),
      '#default_value' => $this->configuration['retry_interval_seconds'] ?? 2,
    ];

    return $form;
  }

  /**
   * Callback for ajax-enabled add and remove node buttons.
   *
   * Selects and returns the fieldset with the nodes in it.
   */
  public function addNodeCallback(array &$form, FormStateInterface $form_state): array {
    return $form['backend_config']['nodes'];
  }

  /**
   * Submit handler for "add another node" button.
   *
   * Increments the max counter and triggers a rebuild.
   */
  public function addNode(array &$form, FormStateInterface $form_state): void {
    $node_field = $form_state->get('num_nodes');

    $add_button = $node_field + 1;
    $form_state->set('num_nodes', $add_button);

    $form_state->setRebuild();
  }

  /**
   * Submit handler for "remove node" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeNode(array $form, FormStateInterface $form_state): void {
    $node_field = $form_state->get('num_nodes');

    if ($node_field > 1) {
      $remove_button = $node_field - 1;
      $form_state->set('num_nodes', $remove_button);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index): void {
    if ($index instanceof IndexInterface) {
      $index = $index->getProcessor('typesense_schema')->getConfiguration()['schema']['name'];
    }
    try {
      $this->typesense->dropCollection($index);
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to remove index @index.', [
        '@index' => $index,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   - Add created/updated column(s) to index.
   */
  public function indexItems(IndexInterface $index, array $items): array {
    try {
      $collection_name = $index->getProcessor('typesense_schema')->getConfiguration()['schema']['name'];
      $indexed_documents = [];

      // Loop over each indexable item.
      foreach ($items as $key => $item) {
        // Start the document with the item id.
        $document = [
          'id' => $key,
        ];

        // Add each contained value to the document.
        foreach ($item->getFields() as $field_name => $field) {
          $field_type = $field->getType();
          $field_values = $field->getValues();
          $value = NULL;

          // Values might be [], so we have to handle that case separately from
          // the main loop-over-the-field-values routine.
          //
          // In either case, we rely on the Typesense service to enfore the
          // datatype.
          if (count($field_values) == 0) {
            $value = $this->typesense->prepareItemValue(NULL, $field_type);
          }
          else {
            foreach ($field_values as $field_value) {
              $value = $this->typesense->prepareItemValue($field_value, $field_type);
            }
          }

          $document[$field_name] = $value;
        }

        // Create the document.
        $this->typesense->createDocument($collection_name, $document);
        $indexed_documents[] = $key;
      }

      return $indexed_documents;
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to index items.'));
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids): void {
    try {
      $this->typesense->deleteDocuments($index->getProcessor('typesense_schema')->getConfiguration()['schema']['name'], ['id' => $item_ids]);
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to delete items @items.', [
        '@items' => implode(', ', $item_ids),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL): void {
    try {
      // The easiest way to remove all items is to drop the collection
      // altogether and then recreate it.
      //
      // This is especially the case, given that the only reason we ever want to
      // delete ALL items is to reindex which, in the case of Typesense, means
      // we are probably also changing the collection schema (which requires
      // deleting it) anyway.
      $this->removeIndex($index->id());
      $this->syncIndexesAndCollections();
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to delete index @index.', [
        '@index' => $index->id(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index): void {
    try {
      if ($this->indexFieldsUpdated($index)) {
        $index->reindex();
        $this->removeIndex($index->getProcessor('typesense_schema')->getConfiguration()['schema']['name']);
        $this->syncIndexesAndCollections();
      }
    }
    catch (SearchApiTypesenseException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger()->addError($this->t('Unable to update index @index.', [
        '@index' => $index->getProcessor('typesense_schema')->getConfiguration()['schema']['name'],
      ]));
    }
  }

  /**
   * Checks if the recently updated index had any fields changed.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that was just updated.
   *
   * @return bool
   *   TRUE if any of the fields were updated, FALSE otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function indexFieldsUpdated(IndexInterface $index): bool {
    if (!isset($index->original)) {
      return TRUE;
    }

    $original = $index->original;

    $old_fields = $original->getFields();
    $new_fields = $index->getFields();

    if (count($old_fields) == 0 && count($new_fields) == 0) {
      return FALSE;
    }

    if (count(array_diff_key($old_fields, $new_fields)) == 0 || count(array_diff_key($new_fields, $old_fields)) == 0) {
      return TRUE;
    }

    $processor_name = 'typesense_schema';
    $old_config = $original->getProcessor($processor_name)->getConfiguration();
    $new_config = $index->getProcessor($processor_name)->getConfiguration();
    $old_schema_config = array_key_exists('schema', $old_config['schema']) ? $old_config['schema']['fields'] : NULL;
    $new_schema_config = array_key_exists('schema', $new_config['schema']) ? $new_config['schema']['fields'] : NULL;

    if ($old_schema_config == NULL || $new_schema_config == NULL) {
      return FALSE;
    }

    if (array_keys($old_schema_config) !== array_keys($new_schema_config)) {
      return TRUE;
    }

    // We're comparing schema fields, so we know something about the array
    // structure. And if got this far, we know they have identical keys too.
    $schema_changed = FALSE;

    foreach ($new_schema_config as $name => $config) {
      if ($config !== $old_schema_config[$name]) {
        // We found a difference--we don't need to keep looking.
        $schema_changed = TRUE;
        break;
      }
    }

    if ($schema_changed) {
      return TRUE;
    }

    // No changes found.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query): void {
    // Will use $this->typesense->searchDocuments();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDataType($type): bool {
    return str_starts_with($type, 'typesense_');
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    try {
      return (bool) $this->getTypesense()?->retrieveDebug()['state'];
    }
    catch (SearchApiTypesenseException $e) {
      return FALSE;
    }
  }

  /**
   * Return the Typesense client.
   */
  public function getTypesense(): ?TypesenseClientInterface {
    return $this->typesense;
  }

}
