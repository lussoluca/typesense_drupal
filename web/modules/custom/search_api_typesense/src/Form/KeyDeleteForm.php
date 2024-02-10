<?php declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Api\TypesenseClientInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Provides a Search API Typesense form.
 */
final class KeyDeleteForm extends ConfirmFormBase {
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
    return 'search_api_typesense_key_delete';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ServerInterface $search_api_server = NULL, int $key_id = NULL): array {
    $backend = $search_api_server->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    if (!$backend->isAvailable()) {
      $this->messenger()->addError(
        $this->t('The Typesense server is not available.')
      );
    }

    $this->typesenseClient = $backend->getTypesense();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   * @throws \Http\Client\Exception
   * @throws \Typesense\Exceptions\TypesenseClientError
   */
  public function submitForm(array &$form, FormStateInterface $form_state, ?ServerInterface $search_api_server = NULL, int $key_id = NULL): void {
    $key = $this->typesenseClient->retrieveKey($this->getRequest()->get('id'));
    $key_data = $key->retrieve();
    $key->delete();

    $this->messenger()->addStatus($this->t('The key <em>":desc"</em> has been deleted.', [
      ':desc' => $key_data['description'],
    ]));

    $form_state->setRedirect('search_api_typesense.server.api_keys', [
      'search_api_server' => 'typesense',
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   * @throws \Http\Client\Exception
   * @throws \Typesense\Exceptions\TypesenseClientError
   */
  public function getQuestion(): TranslatableMarkup {
    $key_data = $this->typesenseClient
      ->retrieveKey($this->getRequest()->get('id'))
      ->retrieve();

    return $this->t('Do you want to delete the key <em>":desc"</em>?', [
      ':desc' => $key_data['description'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('search_api_typesense.server.api_keys', [
      'search_api_server' => 'typesense',
    ]);
  }

}
