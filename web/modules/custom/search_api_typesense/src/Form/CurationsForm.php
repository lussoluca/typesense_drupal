<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;
use Drupal\search_api_typesense\TypesenseTrait;

/**
 * Manage the curations.
 */
final class CurationsForm extends FormBase {

  use TypesenseTrait;

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
    return 'search_api_typesense_curations';
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
    ?IndexInterface $search_api_index = NULL,
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

    $form['#title'] = $this->t('Manage curations for search index %label', ['%label' => $search_api_index->label()]);

    $this->typesenseClient = $backend->getTypesense();
    $documentation_link = Link::fromTextAndUrl(
      $this->t('documentation'),
      Url::fromUri(
        'https://typesense.org/docs/latest/api/curation.html',
        [
          'attributes' => [
            'target' => '_blank',
          ],
        ],
      ),
    );

    $op = $this->getRequest()->query->get('op') ?? 'add';
    $curation = NULL;
    if ($op == 'edit') {
      $curation = $this->typesenseClient->retrieveCuration(
        $search_api_index->id(),
        $this->getRequest()->query->get('id'),
      );
    }

    $curations = $this->typesenseClient->retrieveCurations($search_api_index->id());
    $form['curation'] = [
      '#type' => 'details',
      '#title' => $op == 'edit' ? $this->t('Edit curation') : $this->t('Add curation'),
      '#description' => $this->t('See the @link for more information.', [
        '@link' => $documentation_link->toString(),
      ]),
      '#open' => !(count($curations['overrides']) > 0) || $op == 'edit',
    ];

    $form['curation']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id'),
      '#description' => $this->t('Unique identifier for the curation. It cannot contain the / character.'),
      '#required' => TRUE,
      '#default_value' => $op == 'edit' ? $this->getRequest()->query->get('id') : $this->generateUuid(),
      '#disabled' => $op == 'edit',
    ];

    $form['curation']['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#default_value' => $op == 'edit' ? $curation['query'] : '',
    ];

    $form['curation']['match'] = [
      '#type' => 'select',
      '#title' => $this->t('Match'),
      '#options' => [
        'exact' => $this->t('Exact'),
        'contains' => $this->t('Contains'),
      ],
      '#default_value' => $op == 'edit' ? $curation['match'] : '',
    ];

    $form['curation']['includes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Includes'),
      '#default_value' => $op == 'edit' ? $curation['includes'] : '',
    ];

    $form['curation']['excludes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excludes'),
      '#default_value' => $op == 'edit' ? $curation['excludes'] : '',
    ];

    $form['curation']['filter_curated_hits'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter curated hits'),
      '#default_value' => $op == 'edit' ? $curation['filter_curated_hits'] : FALSE,
    ];

    $form['curation']['remove_matched_tokens'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove matched tokens'),
      '#default_value' => $op == 'edit' ? $curation['remove_matched_tokens'] : FALSE,
    ];

    $form['curation']['stop_processing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stop processing'),
      '#default_value' => $op == 'edit' ? $curation['stop_processing'] : TRUE,
    ];

    $form['index_id'] = [
      '#type' => 'value',
      '#value' => $search_api_index->id(),
    ];

    $form['curation']['operations'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $op == 'edit' ? $this->t('Update') : $this->t('Add new'),
      ],
    ];

    if ($op == 'edit') {
      $form['curation']['operations']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => Url::fromRoute(
          'search_api_typesense.collection.curations', [
            'search_api_index' => $search_api_index->id(),
          ],
        ),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    $form['existing_curations']['list'] = $this->buildExistingCurationsTable($curations, $search_api_index->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->checkValidId($form_state->getValue('id'))) {
      $form_state->setErrorByName('id', $this->t('The id cannot contain the / character.')->render());
    }
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

    if ($form_state->getValue('symbols_to_index') != '') {
      $synonym['symbols_to_index'] = explode(',', $form_state->getValue('symbols_to_index'));
    }

    if ($form_state->getValue('locale') != '') {
      $synonym['locale'] = $form_state->getValue('locale');
    }

    try {
      $response = $this->typesenseClient->createSynonym(
        $form_state->getValue('index_id'),
        $form_state->getValue('id'),
        $synonym,
      );

      $op = $this->getRequest()->query->get('op') ?? 'add';
      if ($op == 'edit') {
        $this->messenger()->addStatus(
          $this->t('Synonym %id has been updated.', [
            '%id' => $response['id'],
          ]),
        );
      }
      else {
        $this->messenger()->addStatus(
          $this->t('Synonym %id has been added.', [
            '%id' => $response['id'],
          ]),
        );
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t('Something went wrong.'));
    }

    $form_state->setRedirect('search_api_typesense.collection.synonyms', [
      'search_api_index' => $form_state->getValue('index_id'),
    ]);
  }

  /**
   * Builds the existing curations table.
   *
   * @param array $curations
   *   The existing curations.
   * @param string $index_id
   *   The index ID.
   *
   * @return array
   *   The existing curations table.
   */
  protected function buildExistingCurationsTable(array $curations, string $index_id): array {
    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Existing curations'),
      '#header' => [
        $this->t('ID'),
        $this->t('Query'),
        $this->t('Match'),
        $this->t('Includes'),
        $this->t('Excludes'),
        $this->t('Filter curated hits'),
        $this->t('Remove matched tokens'),
        $this->t('Stop processing'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No curations found.'),
    ];

    $rows = [];
    foreach ($curations['overrides'] as $key => $value) {
      $rows[$key] = [
        'id' => $value['id'],
        'query' => $value['rule']['query'],
        'match' => $value['rule']['match'],
        'includes' => '',
        'excludes' => '',
        'filter_curated_hits' => $value['filter_curated_hits'] == 1 ? $this->t('Yes') : $this->t('No'),
        'remove_matched_tokens' => $value['remove_matched_tokens'] == 1 ? $this->t('Yes') : $this->t('No'),
        'stop_processing' => $value['stop_processing'] == 1 ? $this->t('Yes') : $this->t('No'),

        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => [
              'edit' => [
                'title' => $this
                  ->t('Edit'),
                'url' => Url::fromRoute(
                  'search_api_typesense.collection.curations', [
                    'search_api_index' => $index_id,
                    'op' => 'edit',
                    'id' => $value['id'],
                  ],
                ),
              ],
            ],
          ],
        ],
      ];
    }
    $table['#rows'] = $rows;

    return $table;
  }

}
