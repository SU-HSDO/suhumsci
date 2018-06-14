<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HsBugherdForm
 *
 * @package Drupal\hs_bugherd\Form
 */
class HsBugherdForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bugherdapi.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_bugherd_api';
  }

  /**
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $jiraIssueService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('hs_bugherd'),
      $container->get('jira_rest_wrapper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, HsBugherd $bugherd_api, JiraRestWrapperService $jira_wrapper) {
    parent::__construct($config_factory);
    $this->requestStack = $request_stack;
    $this->bugherdApi = $bugherd_api;
    $this->jiraIssueService = $jira_wrapper->getIssueService();
  }

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
      '#required' => TRUE,
      //      '#ajax' => [
      //        'callback' => '::formAjaxSubmit',
      //        'wrapper' => 'project-id',
      //      ],
    ];

    $form['project_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Bugherd Project'),
      '#default_value' => $config->get('project_id'),
      '#options' => $this->bugherdApi->getProjects(),
      '#prefix' => '<div id="project-id">',
      '#suffix' => '</div>',
    ];
    $form['jira_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#default_value' => $config->get('jira_project'),
      '#required' => TRUE,
    ];

    $bugherd_statuses = [
      HsBugherd::BUGHERDAPI_BACKLOG => $this->t('Backlog'),
      HsBugherd::BUGHERDAPI_TODO => $this->t('ToDo'),
      HsBugherd::BUGHERDAPI_DOING => $this->t('Doing'),
      HsBugherd::BUGHERDAPI_DONE => $this->t('Done'),
      HsBugherd::BUGHERDAPI_CLOSED => $this->t('Closed'),
    ];
    $form['status_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bugherd to Jira Mapping'),
      '#tree' => TRUE,
    ];

    foreach ($bugherd_statuses as $bugherd_status => $label) {
      $form['status_map'][$bugherd_status] = [
        '#type' => 'number',
        '#title' => $label . $this->t(' Status'),
        '#description' => $this->t('The JIRA status code for a %label status', ['%label' => $label]),
        '#required' => TRUE,
        '#default_value' => $config->get('status_map.' . $bugherd_status),
      ];
    }

    //    dpm($this->bugherdApi->getTasks());
    dpm($this->bugherdApi->getComments('6778513'));
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
      ->set('status_map', $form_state->getValue('status_map'))
      ->save();
  }

}
