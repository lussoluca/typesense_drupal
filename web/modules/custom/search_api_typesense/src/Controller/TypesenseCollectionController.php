<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Controller;

use Drupal\Core\Controller\ControllerBase;

class TypesenseCollectionController extends ControllerBase {

  public function synonyms(): array {
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
