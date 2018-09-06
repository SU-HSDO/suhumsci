<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdResourceBase.
 *
 * @package Drupal\hs_bugherd\Plugin\rest\resource
 */
abstract class BugherdResourceBase extends ResourceBase {

  /**
   * Bugherd API service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * Jira rest service.
   *
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $jiraIssueService;

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Jira project to sync bugherd to.
   *
   * @var string
   */
  protected $jiraProject;

  /**
   * Bugherd Project ID
   *
   * @var int
   */
  protected $bugherdProject;

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
   * Load a specific JIRA issue.
   *
   * @param string $issue_id
   *   Issue ID, ie: DEV-123.
   *
   * @return \biologis\JIRA_PHP_API\Issue
   */
  protected function getJiraIssue($issue_id) {
    return $issue_id ? $this->jiraIssueService->load($issue_id) : FALSE;
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
    // Trim down the descrption to only 5 words so we dont clutter up JIRA with
    // a long paragraph as the summary. Also Jira doesnt like new lines in the
    // Summary
    $description_words = explode(' ', trim(preg_replace("/\r|\n/", "", $task['description'])));
    $title = array_slice($description_words, 0, 5);
    $title = implode(' ', $title);
    $title .= count($description_words) > 5 ? '...' : '';
    return "BUGHERD-{$task['local_task_id']}: $title";
  }

  /**
   * Get the bugherd task that matches the jira issue id using the external_id.
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
   * Get the beherd status from a jira status id.
   *
   * @param int $status
   *   Jira status ID
   *
   * @return string|null
   *   Bugherd Status.
   */
  protected function getTranslatedStatus($status) {
    $config = $this->configFactory->get('bugherdapi.settings');
    $status_map = $config->get('status_map');

    foreach ($status_map as $bugherd_id => $ids) {
      $ids = explode(',', $ids);

      foreach ($ids as &$id) {
        $id = trim($id);
      }

      if (in_array($status, $ids)) {
        return $bugherd_id;
      }
    }
  }

}
