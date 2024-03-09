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

    $configuration = $backend->getConfiguration();
    $all_fields = $backend->getTypesense()->getFields($search_api_index->id());
    $query_by_fields = $backend->getTypesense()->getFieldsForQueryBy($search_api_index->id());
    $facet_number_fields = $backend->getTypesense()->getFieldsForFacetNumber($search_api_index->id());
    $facet_string_fields = $backend->getTypesense()->getFieldsForFacetString($search_api_index->id());

    $build['content'] = [
      '#theme' => 'search_api_typesense_admin_serp',
      '#facet_number_fields' => $facet_number_fields,
      '#facet_string_fields' => $facet_string_fields,
      '#attached' => [
        'library' => [
          'search_api_typesense/search',
        ],
        'drupalSettings' => [
          'search_api_typesense' => [
            'api_key' => $configuration['admin_api_key'],
            'host' => $configuration['browser_client']['host'],
            'port' => $configuration['browser_client']['port'],
            'protocol' => $configuration['browser_client']['protocol'],
            'index' => $search_api_index->id(),
            'all_fields' => $all_fields,
            'query_by_fields' => implode(',', $query_by_fields),
            'facet_number_fields' => $facet_number_fields,
            'facet_string_fields' => $facet_string_fields,
          ],
        ],
      ],
    ];

    return $build;
  }

}
