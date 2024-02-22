<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Api\SearchApiTypesenseException;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Form to delete a key.
 */
class ApiKeyDeleteForm extends ConfirmFormBase {

  /**
   * The Typesense client.
   *
   * @var \Drupal\search_api_typesense\Api\TypesenseClientInterface
   */
  protected TypesenseClientInterface $typesenseClient;

  /**
   * The search API server.
   *
   * @var \Drupal\search_api\ServerInterface|null
   */
  private ?ServerInterface $searchApiServer;

  /**
   * The key ID.
   *
   * @var int|null
   */
  private ?int $keyId;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_api_typesense_api_key_delete';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ServerInterface $search_api_server = NULL,
    int $id = NULL,
  ): array {
    $backend = $search_api_server->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    if (!$backend->isAvailable()) {
      $this->messenger()->addError(
        $this->t('The Typesense server is not available.'),
      );
    }

    $this->searchApiServer = $search_api_server;
    $this->keyId = $id;
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
      $key = $this->typesenseClient->retrieveKey($this->keyId);
      $this->typesenseClient->deleteKey($this->keyId);

      $this->messenger()->addStatus($this->t('Key %description has been deleted.',
        [
          '%description' => $key['description'],
        ]));
    }
    catch (SearchApiTypesenseException $e) {
      $this->messenger()->addError($this->t('The key could not be deleted.'));
    }

    $form_state->setRedirect('search_api_typesense.server.api_keys', [
      'search_api_server' => $this->searchApiServer->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function getQuestion(): TranslatableMarkup {
    $key = $this->typesenseClient->retrieveKey($this->keyId);

    return $this->t('Do you want to delete key %desc?', [
      '%desc' => $key['description'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('search_api_typesense.server.api_keys', [
      'search_api_server' => $this->getRequest()
        ->get('search_api_server')
        ->id(),
    ]);
  }

}
