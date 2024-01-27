<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 *
 */
class TypesenseServerController extends ControllerBase {

  /**
   * @param \Drupal\search_api\ServerInterface $search_api_server
   *
   * @return array
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function metrics(ServerInterface $search_api_server): array {
    $backend = $search_api_server->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    $metrics = $backend->getTypesense()->retrieveMetrics();
    dpm($metrics);
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
