<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Provides a Search API Typesense form.
 */
class ApiKeysForm extends FormBase {

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
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ServerInterface $search_api_server = NULL): array {
    $backend = $search_api_server->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    if (!$backend->isAvailable()) {
      $this->messenger()->addError(
        $this->t('The Typesense server is not available.')
      );

      return $form;
    }

    $this->typesenseClient = $backend->getTypesense();

    $documentation_link = Link::fromTextAndUrl(
      $this->t('documentation'),
      Url::fromUri(
        'https://typesense.org/docs/0.21.0/api/api-keys.html#create-an-api-key', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]
      )
    );

    $form['key'] = array(
      '#type' => 'details',
      '#title' => $this->t('Create API Key'),
      '#description' => $this->t('See the @link for more information.', [
        '@link' => $documentation_link->toString(),
      ]),
      '#open' => TRUE,
    );
    $form['key']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Internal description to identify what the key is for.'),
      '#size' => 30,
      '#required' => TRUE,
    );
    $available_actions_link = Link::fromTextAndUrl(
      $this->t('these tables'),
      Url::fromUri(
        'https://typesense.org/docs/0.25.2/api/api-keys.html#sample-actions', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]
      )
    );
    $form['key']['actions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Actions'),
      '#description' => $this->t('Comma separated list of allowed actions. See @link for possible values.', [
        '@link' => $available_actions_link->toString(),
      ]),
      '#size' => 30,
      '#required' => TRUE,
    );
    $form['key']['collections'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Collections'),
      '#description' => $this->t('Comma separated list of collections that this key is scoped to. Supports regex. Eg: <code>coll.*</code> will match all collections that have "coll" in their name.'),
      '#size' => 30,
      '#required' => TRUE,
    );

    $form['key']['operations'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Add new'),
      ],
    ];

    $form['existing_keys']['list'] = $this->buildExistingKeysTable();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $key = $this->typesenseClient->createKey([
      'description' => $form_state->getValue('description'),
      'actions' => explode(',', $form_state->getValue('actions')),
      'collections' => explode(',', $form_state->getValue('collections')),
    ]);

    $this->messenger()->addStatus(
      $this->t('The new key @description has been generated.', [
        '@description' => $key['description'],
      ])
    );
    $this->messenger()->addWarning(
      $this->t('The generated key is only returned during creation. You want to store this key carefully in a secure place.')
    );
  }

  /**
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  protected function buildExistingKeysTable(): array {
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
    $keys = $this->typesenseClient->getKeys();
    foreach ($keys as $key => $value) {
      $rows[$key] = $value;
    }
    $table['#rows'] = $rows;

    return $table;
  }

}
