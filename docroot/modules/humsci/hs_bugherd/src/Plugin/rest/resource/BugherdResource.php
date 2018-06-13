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
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $jiraRestWrapper;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, JiraRestWrapperService $jira_wrapper, HsBugherd $bugherd_api, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->bugherdApi = $bugherd_api;
    $this->jiraRestWrapper = $jira_wrapper;
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
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($data) {
    if (isset($data['task'])) {
      $response = $this->sendToJira($data)->getKey();
    }
    else {
      $response = $this->sendToBugherd($data);
    }
    return new ResourceResponse($response);
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
   * @param array $task
   *
   * @return \biologis\JIRA_PHP_API\Issue
   */
  protected function sendToJira($task) {
    // Get updated task info.
    $task = $this->bugherdApi->getTask($task['task']['id']) ?: $task;
    $task = $task['task'] ?: $task;

    // The task already has a JIRA issue linked.
    if (empty($issue = $this->loadJiraIssue($task['external_id']))) {
      $issue = $this->createJiraIssue($task);
    }
    return $issue;
  }

  /**
   * Load a specific JIRA issue.
   *
   * @param string $issue_id
   *   Issue ID, ie: DEV-123.
   *
   * @return \biologis\JIRA_PHP_API\Issue
   */
  protected function loadJiraIssue($issue_id) {
    return $this->jiraRestWrapper->getIssueService()->load($issue_id);
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
  protected function getTaskName($task) {
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
  protected function createJiraIssue($task) {
    $issue_service = $this->jiraRestWrapper->getIssueService();
    /** @var \biologis\JIRA_PHP_API\Issue $issue */
    $issue = $issue_service->create();
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
   *
   * @return mixed
   *   Result of the operation.
   */
  protected function setBugherdExternalId($task_id, $external_id) {
    // Just setting the external id value.
    $data = ['external_id' => $external_id];
    return $this->bugherdApi->updateTask($this->getBugherdProject(), $task_id, $data);
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
  protected function buildDescription($task) {
    $description = [];
    $description[] = $task['description'];
    $description[] = "Requestor: {$task['requester']['display_name']}";
    $description[] = "URL: {$task['site']}{$task['url']}";
    $description[] = "Browser: {$task['requester_browser']}";
    if ($screenshot = $task['screenshot_url']) {
      $description[] = "Screenshot: {$task['screenshot_url']}";
    }
    return implode(PHP_EOL, $description);
  }

  /**
   * Search JIRA for an issue with the same summary.
   *
   * @param string $issue_name
   *   Issue summary in JIRA to search.
   *
   * @return \biologis\JIRA_PHP_API\Issue[]
   *   Array of JIRA issues.
   */
  protected function searchJira($issue_name) {
    $jira_project = $this->getJiraProject();
    $issue_service = $this->jiraRestWrapper->getIssueService();
    $search = $issue_service->createSearch();
    $search->search("project = $jira_project and summary ~ '$issue_name'");
    return $search->getIssues();
  }

  /**
   * TODO
   *
   * @param array $data
   *
   * @return mixed
   */
  protected function sendToBugherd($data) {
    return FALSE;
  }

}
