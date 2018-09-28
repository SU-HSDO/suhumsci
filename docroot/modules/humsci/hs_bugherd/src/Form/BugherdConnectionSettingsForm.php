<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Entity\Key;

/**
 * Class BugherdConnectionSettingsForm.
 */
class BugherdConnectionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_bugherd.connection_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bugherd_connection_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('hs_bugherd.connection_settings');

    $keys = [];
    /** @var \Drupal\key\Entity\Key $key */
    foreach (Key::loadMultiple() as $key) {
      $keys[$key->id()] = $key->label();
    }

    $form['api_key'] = [
      '#type' => 'select',
      '#title' => $this->t('BugHerd API key'),
      '#default_value' => $config->get('api_key'),
      '#options' => $keys,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $key = Key::load($form_state->getValue('api_key'));
    $this->config('hs_bugherd.connection_settings')
      ->set('api_key', $key->id())
      ->set('dependencies.config', [$key->getConfigDependencyName()])
      ->save();
  }

}
