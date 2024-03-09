<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_typesense\Plugin\search_api\backend\SearchApiTypesenseBackend;

/**
 * Controller for Typesense index operations.
 */
class TypesenseIndexController extends ControllerBase {

  /**
   * Try the search.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The server.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function search(IndexInterface $search_api_index): array {
    $backend = $search_api_index->getServerInstance()->getBackend();
    if (!$backend instanceof SearchApiTypesenseBackend) {
      throw new \InvalidArgumentException('The server must use the Typesense backend.');
    }

    $build['content'] = [
      '#theme' => 'serp',
      '#attached' => [
        'library' => [
          'search_api_typesense/search',
        ],
      ],
    ];

    return $build;
  }

}
