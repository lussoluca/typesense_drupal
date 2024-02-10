<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Search API Typesense form.
 */
final class SynonymForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_api_typesense_synonym';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    $form['root'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root'),
      '#description' => $this->t('For 1-way synonyms, indicates the root word that words in the synonyms parameter map to.'),
    ];

    $form['synonyms'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Synonyms'),
      '#description' => $this->t('List of words that should be considered as synonyms. Separate words with comma.'),
      '#required' => TRUE,
    ];

    $form['collections'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'a', 'b', 'c',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
