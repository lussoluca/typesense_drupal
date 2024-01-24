<?php

declare(strict_types=1);

namespace Drupal\typesense\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 *
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'typesense.settings';
  }

}
