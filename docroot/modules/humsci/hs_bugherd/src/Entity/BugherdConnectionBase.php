<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class BugherdConnectionBase.
 *
 * @package Drupal\hs_bugherd\Entity
 */
abstract class BugherdConnectionBase extends ConfigEntityBase implements BugherdConnectionInterface {

  use StringTranslationTrait;

  /**
   * The Bugherd Connection ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Bugherd Connection label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var int
   */
  protected $bugherdProject;

  /**
   * @var string
   */
  protected $jiraProject;

  /**
   * @var array
   */
  protected $urls;

  /**
   * @var array
   */
  protected $statusMap;

  /**
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $jiraApi;

  /**
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->jiraApi = \Drupal::service('jira_rest_wrapper_service');
    $this->bugherdApi = \Drupal::service('hs_bugherd');
    $this->logger = \Drupal::logger('hs_bugherd');
  }

  /**
   * {@inheritdoc}
   */
  public function getBugherdProject() {
    return $this->bugherdProject;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMap() {
    return $this->statusMap;
  }

  /**
   * {@inheritdoc}
   */
  public function getJiraProject() {
    return $this->jiraProject;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls() {
    return $this->urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getBugherdStatus($jira_status) {
    foreach ($this->statusMap as $bugherd_status => $jira) {
      $jira = explode(',', $jira);
      if (in_array($jira_status, $jira)) {
        return $bugherd_status;
      }
    }
  }

  /**
   * Load a specific JIRA issue.
   *
   * @param string $issue_id
   *   Issue ID, ie: DEV-123.
   *
   * @return \biologis\JIRA_PHP_API\Issue|bool
   *   The found Jira issue or false if none exists.
   */
  protected function getJiraIssue($issue_id) {
    return $this->jiraApi->getIssueService()->load($issue_id);
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
    // summary.
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

}
