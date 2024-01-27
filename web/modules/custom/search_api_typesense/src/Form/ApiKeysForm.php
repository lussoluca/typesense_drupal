<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Provides a Search API Typesense form.
 */
final class ApiKeysForm extends FormBase {

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

    $form['key'] = array(
      '#type' => 'details',
      '#title' => $this->t('Create API Key'),
      '#description' => $this->t('Documentation @url.', [
        '@url' => 'https://typesense.org/docs/0.21.0/api/api-keys.html#create-an-api-key',
      ]),
      '#open' => TRUE,
    );
    $form['key']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#size' => 30,
      '#required' => TRUE,
    );
    $form['key']['actions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Actions'),
      '#size' => 30,
      '#required' => TRUE,
    );
    $form['key']['collections'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Collections'),
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

    $form['existing_keys']['list'] = $this->buildExistingKeysTable($backend);

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
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

  /**
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  protected function buildExistingKeysTable(SearchApiTypesenseBackend $backend): array {
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
    $keys = $backend->getTypesense()->getKeys();
    foreach ($keys as $key => $value) {
      $rows[$key] = $value;
    }

    $table['#rows'] = $rows;

    return $table;
  }

}
