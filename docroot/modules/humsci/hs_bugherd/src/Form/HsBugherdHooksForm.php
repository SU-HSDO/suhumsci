<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HsBugherdHooksForm.
 *
 * Rebuilds bugherd and jira hooks to target this site's api endpoint.
 *
 * @package Drupal\hs_bugherd\Form
 */
class HsBugherdHooksForm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

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
      $container->get('cache.default'),
      $container->get('hs_bugherd'),
      $container->get('jira_rest_wrapper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache_backend, HsBugherd $bugherd_api, JiraRestWrapperService $jira_wrapper) {
    $this->cacheBackend = $cache_backend;
    $this->bugherdApi = $bugherd_api;
    $this->jiraIssueService = $jira_wrapper->getIssueService();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_bugherd_hooks';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Rebuild All Webhooks?');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('bugherdapi.settings');
    if (!$config->get('project_id') || !$config->get('jira_project')) {
      return [
        '#markup' => $this->t('Bugherd or Jira has not been configured.'),
      ];
    }

    // Display a summary of all the hooks we have for jira and bugherd.
    $form['hooks'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Webhooks'),
      '#description' => $this->t('Ideally these should all point to this website'),
      '#tree' => TRUE,
    ];
    $form['hooks']['bugherd'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bugherd'),
    ];
    $form['hooks']['jira'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JIRA'),
    ];

    $project_hooks = [];
    foreach ($this->getBugherdHooks() as $webhook) {
      $project_hooks[] = $webhook['event'] . ': ' . $webhook['target_url'];
    }
    $form['hooks']['bugherd']['hooks']['#markup'] = implode('<br>', $project_hooks);

    $project_hooks = [];
    foreach ($this->getJiraHooks() as $jira_hook) {
      $project_hooks[] = implode('; ', $jira_hook->events) . ': ' . $jira_hook->url;
    }

    $form['hooks']['jira']['hooks']['#markup'] = implode('<br>', $project_hooks);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    $url .= '/api/hs-bugherd';
    // Testing endpoint.
//    $url = 'https://webhook.site/d413cf81-acb5-4277-84a8-804eb752f45c';

    $config = $this->config('bugherdapi.settings');

    $bugherd_project = $config->get('project_id');

    // Delete all bugherd webhooks for this project.
    foreach ($this->getBugherdHooks(TRUE) as $webhook) {
      $this->bugherdApi->deleteWebhook($webhook['id']);
    }

    // Create new bugherd webhooks for this project for each event.
    $bugherd_events = ['task_create', 'task_update', 'comment', 'task_destroy'];
    foreach ($bugherd_events as $event) {
      try {
        $this->bugherdApi->createWebhook([
          'project_id' => $bugherd_project,
          'event' => $event,
          'target_url' => $url,
        ]);
      }
      catch (\Exception $e) {
        $this->logger('hs_bugherd')
          ->error('Unable to add Bugherd Webook for event %event', ['%event' => $event]);
      }
    }

    $hook_data = [
      'name' => 'Bugherd for ARCH',
      'url' => $url,
      'events' => ['jira:issue_updated', 'comment_created'],
      'filters' => [
        'issue-related-events-section' => $this->getJiraFilter(),
      ],
    ];

    $jira_hooks = $this->getJiraHooks(TRUE);
    // Jira hooks don't exist, so lets make one.
    if (empty($jira_hooks)) {
      $this->jiraIssueService->getCommunicationService()
        ->put('/rest/webhooks/1.0/webhook', (object) $hook_data);
      return;
    }

    foreach ($jira_hooks as $hook_id => $webhook) {
      $this->jiraIssueService->getCommunicationService()
        ->put('/rest/webhooks/1.0/webhook/' . $hook_id, (object) $hook_data);
    }

    Cache::invalidateTags(['hs_bugherd_hooks']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('hs_bugherd.api');
  }

  /**
   * Get a configured jira filter.
   *
   * @return string
   *   Jira filter.
   */
  protected function getJiraFilter() {
    $config = $this->config('bugherdapi.settings');
    $jira_project = $config->get('jira_project');
    return "project = $jira_project and summary ~ 'BUGHERD-*'";
  }

  /**
   * Get the Jira hook for bugherd api (normally only 1).
   *
   * @param bool $ignore_cache
   *   Ignore the cacheed hooks.
   *
   * @return array
   *   Keyed array with the hook id as the array key.
   */
  protected function getJiraHooks($ignore_cache = FALSE) {
    $cache = $this->cacheBackend->get('hs_bugherd_jira_hooks');
    if (!$ignore_cache && $cache) {
      return $cache->data;
    }

    $hooks = [];
    $jira_hooks = $this->jiraIssueService->getCommunicationService()
      ->get('/rest/webhooks/1.0/webhook');
    foreach ($jira_hooks as $jira_hook) {
      if (!empty($jira_hook->filters->{'issue-related-events-section'}) && $jira_hook->filters->{'issue-related-events-section'} == $this->getJiraFilter()) {
        // The hook's id is the last part of a url in the "self" attribute.
        $self_explode = explode('/', $jira_hook->self);
        $id = end($self_explode);
        $hooks[$id] = $jira_hook;
      }
    }
    $this->cacheBackend->set('hs_bugherd_jira_hooks', $hooks, Cache::PERMANENT, ['hs_bugherd_hooks']);
    return $hooks;
  }

  /**
   * Get all the bugherd hooks for this project.
   *
   * @param bool $ignore_cache
   *   Ignore the cacheed hooks.
   *
   * @return array
   *   Array of webhooks.
   */
  protected function getBugherdHooks($ignore_cache = FALSE) {
    $cache = $this->cacheBackend->get('hs_bugherd_bugherd_hooks');
    if (!$ignore_cache && $cache) {
      return $cache->data;
    }

    $config = $this->config('bugherdapi.settings');
    $project_id = $config->get('project_id');

    $hooks = [];
    $bugherd_hooks = $this->bugherdApi->getHooks();
    if (!isset($bugherd_hooks['webhooks'])) {
      return [];
    }
    foreach ($bugherd_hooks['webhooks'] as $webhook) {
      if ($webhook['project_id'] == $project_id) {
        $hooks[] = $webhook;
      }
    }
    $this->cacheBackend->set('hs_bugherd_bugherd_hooks', $hooks, Cache::PERMANENT, ['hs_bugherd_hooks']);
    return $hooks;
  }

}