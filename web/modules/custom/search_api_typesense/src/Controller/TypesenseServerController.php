<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Controller;

use Drupal\Core\Controller\ControllerBase;

class TypesenseServerController extends ControllerBase {

  public function status(): array {
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
