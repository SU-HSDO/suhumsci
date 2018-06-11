<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\bugherdapi\Form\BugherdConfigurationForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HsBugherdForm
 *
 * @package Drupal\hs_bugherd\Form
 */
class HsBugherdForm extends BugherdConfigurationForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('bugherdapi.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BugHerd API key'),
      '#default_value' => $config->get('api_key'),
      '#size' => 60,
    ];
    $form['jira_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#default_value' => $config->get('jira_project'),
    ];
    $service = \Drupal::service('hs_bugherd.jira');
    $service->test();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bugherdapi.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('jira_project', $form_state->getValue('jira_project'))
      ->save();
  }

}
