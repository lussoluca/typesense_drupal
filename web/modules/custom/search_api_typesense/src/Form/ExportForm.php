<?php

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_typesense\Api\SearchApiTypesenseException;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Provides a form for exporting a Typesense collection.
 */
class ExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_api_typesense_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?IndexInterface $search_api_index = NULL,
  ): array {
    try {
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

      $form['#title'] = $this->t('Export index %label',
        ['%label' => $search_api_index->label()]);

      $collection_data = $backend
        ->getTypesense()
        ->exportCollection($search_api_index->id());
      $form['collection_data'] = [
        '#default_value' => json_encode($collection_data, JSON_PRETTY_PRINT),
        '#rows' => 50,
        '#title' => $this->t('Collection data'),
        '#type' => 'textarea',
      ];
    }
    catch (SearchApiTypesenseException | SearchApiException $e) {
      $form['error'] = [
        '#markup' => $this->t('An error occurred while exporting the index: @message',
          [
            '@message' => $e->getMessage(),
          ]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {}

}
