<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\bugherdapi\Form\BugherdConfigurationForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hs_bugherd\HsBugherd;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HsBugherdForm
 *
 * @package Drupal\hs_bugherd\Form
 */
class HsBugherdForm extends BugherdConfigurationForm {

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('hs_bugherd')
    );
  }

  public function __construct(ConfigFactoryInterface $config_factory, HsBugherd $bugherd_api) {
    parent::__construct($config_factory);
    $this->bugherdApi = $bugherd_api;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('bugherdapi.settings');
    $form['project_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Project'),
      '#default_value' => $config->get('project_id'),
      '#options' => $this->bugherdApi->getProjects(),
    ];
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
    /** @var \Drupal\hs_bugherd\HsBugherd $service */
    $service = \Drupal::service('hs_bugherd');
    dpm($service->getTask(6760404));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bugherdapi.settings')
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('jira_project', $form_state->getValue('jira_project'))
      ->save();
  }

}
