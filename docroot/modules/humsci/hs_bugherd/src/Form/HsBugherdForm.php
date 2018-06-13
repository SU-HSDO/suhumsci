<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\bugherdapi\Form\BugherdConfigurationForm;
use Drupal\Core\Config\ConfigFactoryInterface;
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
class HsBugherdForm extends BugherdConfigurationForm {

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
      '#size' => 60,
      //      '#ajax' => [
      //        'callback' => '::formAjaxSubmit',
      //        'wrapper' => 'project-id',
      //      ],
    ];

    $form['project_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Project'),
      '#default_value' => $config->get('project_id'),
      '#options' => $this->bugherdApi->getProjects(),
      '#prefix' => '<div id="project-id">',
      '#suffix' => '</div>',
    ];
    $form['jira_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#default_value' => $config->get('jira_project'),
    ];
    $form['actions']['rebuild_hooks'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild Webhooks'),
      '#submit' => ['::rebuildHooks'],
    ];
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

  /**
   * Form submission handler and hook rebuilder.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function rebuildHooks(array &$form, FormStateInterface $form_state) {
    $bugherd_project_id = $form_state->getValue('project_id');
    $this->submitForm($form, $form_state);
    foreach ($this->bugherdApi->getHooks()['webhooks'] as $webhook) {
      if ($webhook['project_id'] == $bugherd_project_id) {
        $this->bugherdApi->deleteWebhook($webhook['id']);
      }
    }
    $url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    $bugherd_events = ['task_create', 'task_update', 'comment', 'task_destroy'];
    foreach ($bugherd_events as $event) {
      try {
        $this->bugherdApi->createWebhook([
          'project_id' => $bugherd_project_id,
          'event' => $event,
          'target_url' => "$url/api/hs-bugherd",
        ]);
      }
      catch (\Exception $e) {
        $this->logger('hs_bugherd')
          ->error('Unable to add Bugherd Webook for event %event', ['%event' => $event]);
      }
    }
  }

}
