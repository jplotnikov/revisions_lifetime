<?php

namespace Drupal\revisions_lifetime\Form;

/**
 * @file
 * Contains Drupal\revisions_lifetime\Form\RevisionLifetimeConfigForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\revisions_lifetime\Form
 */
class RevisionLifetimeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'revisions_lifetime.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('revisions_lifetime.settings');

    // Get all Content Types.
    $content_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($content_types as $key => $ct) {
      $content_types_items[$key] = $ct->label();
    }

    // Build form.
    $form['content_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content types:'),
      '#description' => $this->t('Content types than will be removed. If nothing is selected, then the revisions of all content types will be deleted'),
    ];
    $form['content_types']['content_types_items'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Content types:'),
      '#options' => $content_types_items,
      '#default_value' => $config->get('content_types_items'),
    ];

    $form['revisions_age'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time period:'),
      '#description' => $this->t('Revisions older than the selected age, will be removed'),
    ];
    $time_period = [
      '3600' => $this->t('Hours'),
      '86400' => $this->t('Days'),
      '604800' => $this->t('Weeks'),
      '2592000' => $this->t('Month')
    ];
    $form['revisions_age']['period'] = [
      '#type' => 'select',
      '#title' => $this->t('Items:'),
      '#options' => $time_period,
      '#default_value' => $config->get('period'),
    ];
    $form['revisions_age']['quantity_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity items:'),
      '#default_value' => $config->get('quantity_items'),
      '#min' => 1,
    ];

    // Delete all old revision for checked content types.
    $form['delete_old_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all revisions'),
      '#description' => $this->t('Delete all old revision for checked content types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Delete all old revision.
    if ($form_state->getValue('delete_old_revision')) {
      $delete = \Drupal::service('revisions_lifetime.revision_cleanup');
      $delete->deleteOldRevision();
    }

    // Save settings for content types and lifetime.
    $config = \Drupal::configFactory()->getEditable('revisions_lifetime.settings')
      ->set('period', $form_state->getValue('period'))
      ->set('quantity_items', $form_state->getValue('quantity_items'))
      ->set('content_types_items', $form_state->getValue('content_types_items'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
