<?php

declare(strict_types = 1);

namespace Drupal\typesense\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for search routes.
 */
final class SearchController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#theme' => 'serp',
      '#attached' => [
        'library' => [
          'typesense/search',
        ],
      ],
    ];

    return $build;
  }

}
