<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Manage the synonyms.
 */
final class SynonymForm extends FormBase {

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
    return 'search_api_typesense_synonym';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?IndexInterface $search_api_index = NULL
  ): array {
    $search_api_server = $search_api_index->getServerInstance();
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
        'https://typesense.org/docs/0.25.2/api/synonyms.html#create-or-update-a-synonym',
        [
          'attributes' => [
            'target' => '_blank',
          ],
        ],
      ),
    );

    $synonyms = $this->typesenseClient->retrieveSynonyms($search_api_index->id());
    $form['synonym'] = [
      '#type' => 'details',
      '#title' => $this->t('Add synonym'),
      '#description' => $this->t('See the @link for more information.', [
        '@link' => $documentation_link->toString(),
      ]),
      '#open' => !(count($synonyms['synonyms']) > 0),
    ];

    $form['synonym']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id'),
      '#required' => TRUE,
    ];

    $form['synonym']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'one_way' => $this->t('One-way'),
        'multi_way' => $this->t('Multi-way'),
      ],
    ];

    $form['synonym']['root'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root'),
      '#description' => $this->t('For 1-way synonyms, indicates the root word that words in the synonyms parameter map to.'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'multi_way'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'multi_way'],
        ],
      ],
    ];

    $form['synonym']['synonyms'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Synonyms'),
      '#description' => $this->t('List of words that should be considered as synonyms. Separate words with comma.'),
      '#required' => TRUE,
    ];

    $form['synonym']['symbols_to_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Symbols to index'),
      '#description' => $this->t('By default, special characters are dropped from synonyms. Use this attribute to specify which special characters should be indexed as is.'),
    ];

    $form['synonym']['locale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale'),
      '#description' => $this->t('Locale for the synonym, leave blank to use the standard tokenizer.'),
    ];

    $form['index_id'] = [
      '#type' => 'value',
      '#value' => $search_api_index->id(),
    ];

    $form['synonym']['operations'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Add new'),
      ],
    ];

    $form['existing_synonyms']['list'] = $this->buildExistingSynonymsTable($synonyms, $search_api_index->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $synonym = [];

    if ($form_state->getValue('type') == 'multi_way') {
      $synonym['synonyms'] = explode(',', $form_state->getValue('synonyms'));
    }
    else {
      $synonym['root'] = $form_state->getValue('root');
      $synonym['synonyms'] = explode(',', $form_state->getValue('synonyms'));
    }

    $response = $this->typesenseClient->createSynonym(
      $form_state->getValue('index_id'),
      $form_state->getValue('id'),
      $synonym,
    );

    $this->messenger()->addStatus(
      $this->t('Synonym %id has been added.', [
        '%id' => $response['id'],
      ]),
    );
  }

  /**
   * Builds the existing keys table.
   *
   * @param array $synonyms
   *   The existing synonyms.
   * @param string $index_id
   *   The index ID.
   *
   * @return array
   *   The existing keys table.
   */
  protected function buildExistingSynonymsTable(array $synonyms, string $index_id): array {
    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Existing synonyms'),
      '#header' => [
        $this->t('ID'),
        $this->t('Type'),
        $this->t('Root'),
        $this->t('Synonyms'),
        $this->t('Symbols to index'),
        $this->t('Locale'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No synonyms found.'),
    ];

    $rows = [];
    foreach ($synonyms['synonyms'] as $key => $value) {
      $rows[$key] = [
        'id' => $value['id'],
        'type' => $value['root'] == '' ? $this->t('Multi-way') : $this->t('One-way'),
        'root' => $value['root'],
        'synonyms' => implode(', ', $value['synonyms']),
        'symbols_to_index' => array_key_exists('symbols_to_index', $value) ? implode(', ', $value['symbols_to_index']) : '',
        'locale'  => array_key_exists('locale', $value) ? $value['locale'] : '',
        'operations' => Link::fromTextAndUrl(
          $this->t('Delete'),
          Url::fromRoute(
            'search_api_typesense.collection.synonyms.delete', [
              'search_api_index' => $index_id,
              'id' => $value['id'],
            ],
          ),
        ),
      ];
    }
    $table['#rows'] = $rows;

    return $table;
  }

}
