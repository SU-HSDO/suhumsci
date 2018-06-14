<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdResource
 *
 * @RestResource(
 *   id = "hs_bugherd_resource",
 *   label = @translation("HS Bugherd Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/hs-bugherd",
 *     "https://www.drupal.org/link-relations/create" = "/api/hs-bugherd"
 *   }
 * )
 */
class BugherdResource extends ResourceBase {

  /**
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $jiraIssueService;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var string
   */
  protected $jiraProject;

  /**
   * @var string
   */
  protected $bugherdProject;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, JiraRestWrapperService $jira_wrapper, HsBugherd $bugherd_api, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->bugherdApi = $bugherd_api;
    $this->jiraIssueService = $jira_wrapper->getIssueService();
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('jira_rest_wrapper_service'),
      $container->get('hs_bugherd'),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to entity POST requests.
   *
   * @param array $data
   *   Post data from API.
   *
   * @return \Drupal\rest\ResourceResponse
   * @throws \Exception
   */
  public function post(array $data) {
    // Data from jira has this key. Bugherd does not.
    if (isset($data['webhookEvent'])) {
      return new ResourceResponse($this->sendToBugherd($data));
    }
    return new ResourceResponse($this->sendToJira($data));
  }

  /**
   * Returns the JIRA project key.
   *
   * @return string
   *   Key.
   */
  protected function getJiraProject() {
    if (!empty($this->jiraProject)) {
      return $this->jiraProject;
    }
    $this->jiraProject = $this->configFactory->get('bugherdapi.settings')
      ->get('jira_project');
    return $this->jiraProject;
  }

  /**
   * Returns the bugherd project ID.
   *
   * @return string
   *   ID.
   */
  protected function getBugherdProject() {
    if (!empty($this->bugherdProject)) {
      return $this->bugherdProject;
    }
    $this->bugherdProject = $this->configFactory->get('bugherdapi.settings')
      ->get('project_id');
    return $this->bugherdProject;
  }

  /**
   * Array of data from bugherd.
   *
   * @param array $data
   *   Bugherd api data.
   *
   * @return string
   *   The Jira issue that was created/updated.
   */
  protected function sendToJira(array $data) {
    if (isset($data['comment'])) {
      if (empty($data['comment']['user']['email'])) {
        return $this->t('Comment rejected from Anonymous');
      }

      // Comment was added in Bugherd.
      $task = $data['comment']['task'];
    }
    else {
      // Task was created in bugherd.
      // When a task is created it doesnt have all the info so we do a call to Get
      // updated task info.
      $task = $this->bugherdApi->getTask($data['task']['id']) ?: $data;
      $task = $task['task'] ?: $task;
    }

    // The task already has a JIRA issue linked.
    if (empty($issue = $this->getJiraIssue($task['external_id']))) {
      $issue = $this->createJiraIssue($task);
    }

    if (isset($data['comment'])) {
      // Add the comment now.
      $issue->addComment($this->t('From ') . $data['comment']['user']['display_name'] . PHP_EOL . $data['comment']['text']);
    }
    return $issue->getKey();
  }

  /**
   * Load a specific JIRA issue.
   *
   * @param string $issue_id
   *   Issue ID, ie: DEV-123.
   *
   * @return \biologis\JIRA_PHP_API\Issue
   */
  protected function getJiraIssue($issue_id) {
    return $this->jiraIssueService->load($issue_id);
  }

  /**
   * Get the configured task name to be created in JIRA.
   *
   * @param array $task
   *   Array of task values from bugherd.
   *
   * @return string
   *   Created name.
   */
  protected function getTaskName(array $task) {
    return "BUGHERD-{$task['local_task_id']}: {$task['description']}";
  }

  /**
   * Create a new JIRA issue from the bugherd task.
   *
   * @param array $task
   *   Array of bugherd values.
   *
   * @return \biologis\JIRA_PHP_API\Issue
   *   Created JIRA issue.
   */
  protected function createJiraIssue(array $task) {
    /** @var \biologis\JIRA_PHP_API\Issue $issue */
    $issue = $this->jiraIssueService->create();
    $issue->fields->project->setKey($this->getJiraProject());
    $issue->fields->setDescription($this->buildDescription($task));
    // Issue type : Bug
    $issue->fields->issuetype->setId('1');
    $issue->fields->addGenericJiraObject('priority');
    //Priority Minor
    $issue->fields->priority->setId('4');
    $issue->fields->setSummary($this->getTaskName($task));

    //create the parent issue
    $issue->save();
    $this->setBugherdExternalId($task['id'], $issue->getKey());
    return $issue;
  }

  /**
   * Sync up the bugherd task with the JIRA issue key.
   *
   * @param int $task_id
   *   Bugherd ID.
   * @param string $external_id
   *   JIRA Key.
   * @param int|null $project_id
   *   Bugherd Project ID if available.
   *
   * @return mixed
   *   Result of the operation.
   */
  protected function setBugherdExternalId($task_id, $external_id, $project_id = NULL) {
    // Just setting the external id value.
    $data = ['external_id' => $external_id];
    return $this->bugherdApi->updateTask($task_id, $data, $project_id);
  }

  /**
   * Create a usable description for JIRA from bugherd data.
   *
   * @param array $task
   *   Bugherd Data.
   *
   * @return string
   *   Build description.
   */
  protected function buildDescription(array $task) {
    $description = [];
    $description[] = $task['description'];
    $description[] = '';
    $description[] = "Requestor: {$task['requester']['display_name']}";
    $description[] = "URL: {$task['site']}{$task['url']}";
    $description[] = "Browser: {$task['requester_browser']}";
    $description[] = "Browser size: {$task['requester_browser_size']}";
    $description[] = "Browser size: {$task['requester_resolution']}";
    $description[] = "Item Selector: {$task['selector_info']['path']}";

    if ($screenshot = $task['screenshot_url']) {
      $description[] = "Screenshot: {$task['screenshot_url']}";
    }
    return implode(PHP_EOL, $description);
  }

  /**
   * @param array $data
   *
   * @return bool|mixed
   * @throws \Exception
   */
  protected function sendToBugherd(array $data) {
    $issue_key = $data['issue']['key'];
    if (!($bugherd_task = $this->getBugherdTask($issue_key))) {
      return FALSE;
    }

    if (!$this->isNewBugherdComment($bugherd_task, $data['comment'])) {
      return $this->t('Comment rejected from @name', ['@name' => $data['comment']['author']['name']]);
    }

    switch ($data['webhookEvent']) {
      case 'comment_created':
        $comment = [
          'text' => $data['comment']['author']['displayName'] . ': ' . $data['comment']['body'],
        ];
        return $this->bugherdApi->addComment($bugherd_task['id'], $comment, $bugherd_task['project_id']);
        break;

      case 'jira:issue_updated':
        if ($data['changelog']['items'][0]['field'] == 'status') {
          $jira_status = $data['changelog']['items'][0]['to'];
          $status = ['status' => $this->getTranslatedStatus($jira_status)];
          return $this->bugherdApi->updateTask($bugherd_task['id'], $status);
        }
    }

    return FALSE;
  }

  /**
   * Get the bugherd task that matches the jira issue id.
   *
   * @param string $jira_issue
   *   Jira issue id.
   *
   * @return bool|array
   *   Task data array or false if none found.
   */
  protected function getBugherdTask($jira_issue) {
    $response = $this->bugherdApi->getTasks(NULL, ['external_id' => $jira_issue]);
    if (empty($response['tasks'][0])) {
      return FALSE;
    }
    return $response['tasks'][0];
  }

  /**
   * @param array $bugherd_task
   * @param array $comment
   *
   * @return bool
   */
  protected function isNewBugherdComment(array $bugherd_task, array $comment) {
    $comments = $this->bugherdApi->getComments($bugherd_task['id']);
    foreach ($comments as $comment) {
      if (strpos($comment['body'], $comment['text']) !== FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the beherd status from a jira status id.
   *
   * @param int $status
   *   Jira status ID
   *
   * @return string
   *   Bugherd Status.
   */
  protected function getTranslatedStatus($status) {
    $config = $this->configFactory->get('bugherdapi.settings');
    $status_map = $config->get('status_map');
    $status_map = array_flip($status_map);
    return $status_map[$status] ?: HsBugherd::BUGHERDAPI_TODO;
  }

}
