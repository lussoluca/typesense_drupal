<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class TypesenseCollectionController extends ControllerBase {

  /**
   *
   */
  public function synonyms(): array {
    $data = '{
      "synonyms": [
        {
          "id": "coat-synonyms",
          "root": "",
          "synonyms": ["blazer", "coat", "jacket"]
        }
      ]
    }';
    $synonyms = Json::decode($data);

    // dump($synonyms);
    return [
      '#type' => 'table',
      '#header' => [t('ID'), t('Root'), t('Synonyms')],
      '#rows' => $synonyms['synonyms'],
      '#attributes' => [
        'class' => ['my-table'],
      ],
    ];
  }

}
