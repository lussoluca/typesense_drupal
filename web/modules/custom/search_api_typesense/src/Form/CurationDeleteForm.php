<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_typesense\Api\SearchApiTypesenseException;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Form to delete a curation.
 */
class CurationDeleteForm extends ConfirmFormBase {

  /**
   * The Typesense client.
   *
   * @var \Drupal\search_api_typesense\Api\TypesenseClientInterface
   */
  protected TypesenseClientInterface $typesenseClient;

  /**
   * The search API index.
   *
   * @var \Drupal\search_api\IndexInterface|null
   */
  private ?IndexInterface $searchApiIndex;

  /**
   * The curation ID.
   *
   * @var string|null
   */
  private ?string $curationId;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_api_typesense_curation_delete';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    IndexInterface $search_api_index = NULL,
    string $id = NULL,
  ): array {
    $backend = $search_api_index->getServerInstance()->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    if (!$backend->isAvailable()) {
      $this->messenger()->addError(
        $this->t('The Typesense server is not available.'),
      );
    }

    $this->searchApiIndex = $search_api_index;
    $this->curationId = $id;
    $this->typesenseClient = $backend->getTypesense();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    try {
      $curation = $this->typesenseClient->deleteCuration($this->searchApiIndex->id(),
        $this->curationId);

      $this->messenger()->addStatus($this->t('Curation %id has been deleted.', [
        '%id' => $curation['id'],
      ]));
    }
    catch (SearchApiTypesenseException $e) {
      $this->messenger()
        ->addError($this->t('The curation could not be deleted.'));
    }

    $form_state->setRedirect('search_api_typesense.collection.curations', [
      'search_api_index' => $this->searchApiIndex->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function getQuestion(): TranslatableMarkup {
    $curation = $this->typesenseClient->retrieveCuration($this->searchApiIndex->id(),
      $this->curationId);

    return $this->t('Do you want to delete curation %id?', [
      '%id' => $curation['id'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('search_api_typesense.collection.curations', [
      'search_api_index' => $this->searchApiIndex->id(),
    ]);
  }

}
