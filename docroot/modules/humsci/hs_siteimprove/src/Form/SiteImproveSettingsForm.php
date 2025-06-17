<?php

namespace Drupal\hs_siteimprove\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the basic SiteImprove settings.
 */
class SiteImproveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'hs_siteimprove.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_improve_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hs_siteimprove.settings');

    $form['#tree'] = TRUE;
    $form['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API endpoint base URL'),
      '#description' => $this->t('The AFT Connect API endpoint base URL.'),
      '#default_value' => $config->get('base_url'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The SiteImprove username.'),
      '#default_value' => $config->get('username'),
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The SiteImprove API key.'),
      '#default_value' => $config->get('api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('hs_siteimprove.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('username', $form_state->getValue('username'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
  }

}
