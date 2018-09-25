<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Component\Utility\SortArray;
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
   * Bugherd API service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * Jira wrapper service.
   *
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $jiraIssueService;

  /**
   * Default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hs_bugherd'),
      $container->get('jira_rest_wrapper_service'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(HsBugherd $bugherd_api, JiraRestWrapperService $jira_wrapper, CacheBackendInterface $cache_backend) {
    $this->bugherdApi = $bugherd_api;
    $this->jiraIssueService = $jira_wrapper->getIssueService();
    $this->cacheBackend = $cache_backend;
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
    $bugherd_projects = $this->bugherdApi->getProjects();

    foreach ($this->getBugherdHooks() as $webhook) {
      $hook = [
        $webhook['event'],
        $webhook['project_id'] ? $bugherd_projects[$webhook['project_id']] : '',
        $webhook['target_url'],
      ];

      $project_hooks[] = implode(', ', array_filter($hook));
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
    // Delete all bugherd webhooks for this project.
    foreach ($this->getBugherdHooks() as $webhook) {
      $this->bugherdApi->deleteWebhook($webhook['id']);
    }

    // Create new bugherd webhooks for this project for each event.
    $bugherd_events = ['task_create', 'task_update', 'comment', 'task_destroy'];
    foreach ($bugherd_events as $event) {
      try {
        $this->bugherdApi->createWebhook([
          'event' => $event,
          'target_url' => $this->getHookUrl() . '/bugherd',
        ]);
      }
      catch (\Exception $e) {
        $this->messenger()
          ->addError($this->t('Unable to add Bugherd Webook for event %event. More info in database logs', ['%event' => $event]));

        $this->logger('hs_bugherd')
          ->error('Unable to add Bugherd Webook for event %event, @error', [
            '%event' => $event,
            '@error' => $e->getMessage(),
          ]);
      }
    }

    $this->addJiraHook();
    Cache::invalidateTags(['hs_bugherd:hooks']);
  }

  /**
   * Add the Jira hook via the Jira API.
   */
  protected function addJiraHook() {
    $hook_data = [
      'name' => 'Bugherd Webhook',
      'url' => $this->getHookUrl() . '/jira',
      'events' => ['jira:issue_updated', 'comment_created'],
      'filters' => [
        'issue-related-events-section' => $this->getJiraFilter(),
      ],
    ];

    $jira_hooks = $this->getJiraHooks();
    // Jira hooks don't exist, so lets make one.
    if (empty($jira_hooks)) {
      $this->jiraIssueService->getCommunicationService()
        ->post('/rest/webhooks/1.0/webhook', (object) $hook_data);
    }

    foreach (array_keys($jira_hooks) as $hook_id) {
      $this->jiraIssueService->getCommunicationService()
        ->put('/rest/webhooks/1.0/webhook/' . $hook_id, (object) $hook_data);
    }
  }

  /**
   * Get the url to be used for the webhooks.
   *
   * @return string
   *   Local url to API.
   */
  protected function getHookUrl() {
    $url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    return $url . '/api/hs-bugherd';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.bugherd_connection.collection');
  }

  /**
   * Get a configured jira filter.
   *
   * @return string
   *   Jira filter.
   */
  protected function getJiraFilter() {
    return "summary ~ 'BUGHERD-*'";
  }

  /**
   * Get the Jira hook for bugherd api (normally only 1).
   *
   * @return array
   *   Keyed array with the hook id as the array key.
   */
  protected function getJiraHooks() {
    if ($cache = $this->cacheBackend->get('hs_bugherd:jira_hooks')) {
      return $cache->data;
    }

    $hooks = [];
    $jira_hooks = $this->jiraIssueService->getCommunicationService()
      ->get('/rest/webhooks/1.0/webhook') ?: [];

    foreach ($jira_hooks as $jira_hook) {
      if (!empty($jira_hook->filters->{'issue-related-events-section'}) && $jira_hook->filters->{'issue-related-events-section'} == $this->getJiraFilter()) {
        // The hook's id is the last part of a url in the "self" attribute.
        $self_explode = explode('/', $jira_hook->self);
        $id = end($self_explode);
        $hooks[$id] = $jira_hook;
      }
    }
    $this->cacheBackend->set('hs_bugherd:jira_hooks', $hooks, Cache::PERMANENT, ['hs_bugherd:hooks']);
    return $hooks;
  }

  /**
   * Get all the bugherd hooks for this project.
   *
   * @return array
   *   Array of webhooks.
   */
  protected function getBugherdHooks() {
    if ($cache = $this->cacheBackend->get('hs_bugherd:bugherd_hooks')) {
      return $cache->data;
    }

    $bugherd_hooks = $this->bugherdApi->getHooks();
    $hooks = $bugherd_hooks['webhooks'] ?? [];
    uasort($hooks, function ($a, $b) {
      return SortArray::sortByKeyString($a, $b, 'target_url');
    });

    $this->cacheBackend->set('hs_bugherd:bugherd_hooks', $hooks, Cache::PERMANENT, ['hs_bugherd:hooks']);
    return $hooks;
  }

}
