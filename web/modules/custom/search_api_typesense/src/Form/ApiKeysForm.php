<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Manage the API keys.
 */
class ApiKeysForm extends FormBase {

  /**
   * The Typesense client.
   *
   * @var \Drupal\search_api_typesense\Api\TypesenseClientInterface
   */
  protected TypesenseClientInterface $typesenseClient;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_api_typesense_api_keys';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   * @throws \Http\Client\Exception
   * @throws \Typesense\Exceptions\TypesenseClientError
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?ServerInterface $search_api_server = NULL,
  ): array {
    $backend = $search_api_server->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    if (!$backend->isAvailable()) {
      $this->messenger()->addError(
        $this->t('The Typesense server is not available.'),
      );

      return $form;
    }

    $this->typesenseClient = $backend->getTypesense();
    $documentation_link = Link::fromTextAndUrl(
      $this->t('documentation'),
      Url::fromUri(
        'https://typesense.org/docs/0.21.0/api/api-keys.html#create-an-api-key',
        [
          'attributes' => [
            'target' => '_blank',
          ],
        ],
      ),
    );

    $keys = $this->typesenseClient->getKeys()->retrieve();
    $form['key'] = [
      '#type' => 'details',
      '#title' => $this->t('Create API Key'),
      '#description' => $this->t('See the @link for more information.', [
        '@link' => $documentation_link->toString(),
      ]),
      '#open' => !(count($keys['keys']) > 0),
    ];

    $form['key']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Internal description to identify what the key is for.'),
      '#size' => 30,
      '#required' => TRUE,
    ];

    $form['key']['actions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Actions'),
      '#description' => $this->t('List of allowed actions'),
      '#options' => $this->listActions(),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $form['key']['collections'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collections'),
      '#description' => $this->t('Comma separated list of collections that this key is scoped to. Supports regex. Eg: <code>coll.*</code> will match all collections that start with "coll".'),
      '#size' => 30,
      '#required' => TRUE,
    ];

    $form['key']['operations'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Add new'),
      ],
    ];

    $form['existing_keys']['list'] = $this->buildExistingKeysTable($search_api_server->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $response = $this->typesenseClient->createKey([
      'description' => $form_state->getValue('description'),
      'actions' => array_keys(array_filter($form_state->getValue('actions'))),
      'collections' => explode(',', $form_state->getValue('collections')),
    ]);

    $this->messenger()->addStatus(
      $this->t('The new key <code>@value</code> has been generated.', [
        '@value' => $response['value'],
      ]),
    );
    $this->messenger()->addWarning(
      $this->t('The generated key is only returned during creation. You need to store this key carefully in a secure place.'),
    );
  }

  /**
   * Builds the existing keys table.
   *
   * @param string $server_id
   *   The server ID.
   *
   * @return array
   *   The existing keys table.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   * @throws \Http\Client\Exception
   * @throws \Typesense\Exceptions\TypesenseClientError
   */
  protected function buildExistingKeysTable(string $server_id): array {
    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Existing API Keys'),
      '#header' => [
        $this->t('ID'),
        $this->t('Key prefix'),
        $this->t('Description'),
        $this->t('Actions'),
        $this->t('Collections'),
        $this->t('Expires at'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No keys found.'),
    ];

    $rows = [];
    $keys = $this->typesenseClient->getKeys()->retrieve();
    foreach ($keys['keys'] as $key => $value) {
      $rows[$key] = [
        'id' => $value['id'],
        'key_prefix' => $value['value_prefix'],
        'description' => $value['description'],
        'actions' => '[' . implode(', ', $value['actions']) . ']',
        'collections' => '[' . implode(', ', $value['collections']) . ']',
        'expires_at' => $value['expires_at'] === 64723363199 ? 'never' : DrupalDateTime::createFromTimestamp($value['expires_at'])
          ->format(DateTimePlus::FORMAT),
        'operations' => Link::fromTextAndUrl(
          $this->t('Delete'),
          Url::fromRoute(
            'search_api_typesense.server.api_keys.delete', [
              'search_api_server' => $server_id,
              'id' => $value['id'],
            ],
          ),
        ),
      ];
    }
    $table['#rows'] = $rows;

    return $table;
  }

  /**
   * List all the available actions.
   *
   * @return string[]
   *   The list of actions.
   */
  private function listActions(): array {
    return [
      'collections:create' => 'collections:create',
      'collections:delete' => 'collections:delete',
      'collections:get' => 'collections:get',
      'collections:list' => 'collections:list',
      'collections:*' => 'collections:*',
      'documents:search' => 'documents:search',
      'documents:get' => 'documents:get',
      'documents:create' => 'documents:create',
      'documents:upsert' => 'documents:upsert',
      'documents:update' => 'documents:update',
      'documents:delete' => 'documents:delete',
      'documents:import' => 'documents:import',
      'documents:export' => 'documents:export',
      'documents:*' => 'documents:*',
      'aliases:list' => 'aliases:list',
      'aliases:get' => 'aliases:get',
      'aliases:create' => 'aliases:create',
      'aliases:delete' => 'aliases:delete',
      'aliases:*' => 'aliases:*',
      'synonyms:list' => 'synonyms:list',
      'synonyms:get' => 'synonyms:get',
      'synonyms:create' => 'synonyms:create',
      'synonyms:delete' => 'synonyms:delete',
      'synonyms:*' => 'synonyms:*',
      'overrides:list' => 'overrides:list',
      'overrides:get' => 'overrides:get',
      'overrides:create' => 'overrides:create',
      'overrides:delete' => 'overrides:delete',
      'overrides:*' => 'overrides:*',
      'keys:list' => 'keys:list',
      'keys:get' => 'keys:get',
      'keys:create' => 'keys:create',
      'keys:delete' => 'keys:delete',
      'keys:*' => 'keys:*',
      'metrics.json:list' => 'metrics.json:list',
      'stats.json:list' => 'stats.json:list',
      'debug:list' => 'debug:list',
      '*' => '*',
    ];
  }

}
