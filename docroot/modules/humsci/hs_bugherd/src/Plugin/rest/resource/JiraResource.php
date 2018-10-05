<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\rest\ResourceResponse;

/**
 * Class JiraResource.
 *
 * API for Jira webhooks to collect and transfer data to Bugherd.
 *
 * @RestResource(
 *   id = "hs_bugherd_resource_jira",
 *   label = @translation("HS Bugherd Resource Jira"),
 *   uri_paths = {
 *     "canonical" = "/api/hs-bugherd/jira",
 *     "https://www.drupal.org/link-relations/create" = "/api/hs-bugherd/jira"
 *   }
 * )
 */
class JiraResource extends HsBugherdResourceBase {

  /**
   * API post call to respond to Jira webhook call.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   API Response.
   *
   * @throws \Exception
   */
  public function post(array $jira_data) {
    $this->setBugherdConnection($this->getOriginalJiraProject($jira_data));
    if (!$this->bugherdConnection) {
      return new ResourceResponse($this->t('No connection data'));
    }

    $bugherd_task = $this->getBugherdTask($this->getOriginalJiraKey($jira_data));

    switch ($jira_data['webhookEvent']) {
      case 'comment_created':
        $this->addBugherdComment($jira_data['comment'], $bugherd_task['id']);
        break;

      case 'jira:issue_updated':
        $this->updateBugherdTask($jira_data, $bugherd_task);
        break;
    }

    $response = new ResourceResponse($jira_data);
    // Don't cache the responses.
    $response->setMaxAge(0);
    $build = ['#cache' => ['max-age' => 0]];
    $response->addCacheableDependency($build);
    return $response;
  }

  /**
   * Update a Bugherd task with the given Jira webhook data.
   *
   * @param array $jira_data
   *   Jira webhook data.
   * @param array $bugherd_task
   *   Bugherd task data.
   *
   * @throws \Exception
   */
  protected function updateBugherdTask(array $jira_data, array $bugherd_task) {
    $changes = [];
    foreach ($jira_data['changelog']['items'] as $change) {
      if ($change['field'] == 'status') {
        $new_status = $this->bugherdConnection->getBugherdStatus($change['to']);
        $changes['status'] = $new_status;
      }

      if ($change['field'] == 'Key') {
        $changes += $this->getKeyChanges($jira_data, $bugherd_task);
      }
    }

    if ($changes) {
      $this->bugherdApi->updateTask($bugherd_task['id'], $changes, $bugherd_task['project_id']);
    }
  }

  /**
   * Get changes when the Jira key changes.
   *
   * @param array $jira_data
   *   Jira webhook data.
   * @param array $bugherd_task
   *   Bugherd task data.
   *
   * @return array
   *   Array of changes to set on the Bugherd task.
   *
   * @throws \Exception
   */
  protected function getKeyChanges(array $jira_data, array $bugherd_task) {
    $changes['external_id'] = $this->getCurrentJiraKey($jira_data);
    $current_project = $this->getCurrentJiraProject($jira_data);
    $connection_mapped = FALSE;
    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach (BugherdConnection::loadMultiple() as $connection) {
      if ($connection->getJiraProject() == $current_project) {
        $connection_mapped = TRUE;
      }
    }

    if (!$connection_mapped) {
      $changes['status'] = HsBugherd::BUGHERDAPI_CLOSED;
      $comment = [
        'author' => ['displayName' => $jira_data['user']['displayName']],
        'body' => $this->t('Connection Mapping lost. Refer to Jira ticket @key', ['@key' => $changes['external_id']]),
      ];;
      $this->addBugherdComment($comment, $bugherd_task['id']);
    }
    return $changes;
  }

  /**
   * Set the Bugherd Connection entity.
   *
   * @param string $jira_project
   *   Jira project key.
   */
  protected function setBugherdConnection($jira_project) {
    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach (BugherdConnection::loadMultiple() as $connection) {
      if ($connection->getJiraProject() == $jira_project) {
        $this->bugherdConnection = $connection;
        break;
      }
    }
  }

  /**
   * Add a comment to Bugherd Task.
   *
   * @param array $comment
   *   Jira comment data.
   * @param int $bugherd_task_id
   *   Bugherd task ID.
   *
   * @return mixed
   *   API Response.
   *
   * @throws \Exception
   */
  protected function addBugherdComment(array $comment, $bugherd_task_id) {
    $comment = [
      'text' => $comment['author']['displayName'] . ': ' . $comment['body'],
    ];
    if ($this->isNewComment($comment, $bugherd_task_id)) {
      return $this->bugherdApi->addComment($bugherd_task_id, $comment, $this->bugherdConnection->getBugherdProject());
    }
    return $this->t('Repeated comment: @body', ['@body' => $comment['body']]);
  }

  /**
   * Check if the comment is new to bugherd, prevent circular commenting.
   *
   * @param array $new_comment
   *   Jira comment data.
   * @param int $bugherd_task_id
   *   Bugherd task id.
   *
   * @return bool
   *   If the comment is new.
   */
  protected function isNewComment(array $new_comment, $bugherd_task_id) {
    $comments = $this->bugherdApi->getComments($bugherd_task_id);
    foreach ($comments['comments'] as $comment) {
      if (strpos($new_comment['body'], $comment['text']) !== FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the associated Bugherd task to the given Jira issue.
   *
   * @param string $jira_key
   *   Jira issue key.
   *
   * @return bool|array
   *   Bugherd task data or false if not found.
   */
  protected function getBugherdTask($jira_key) {
    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach (BugherdConnection::loadMultiple() as $connection) {
      $response = $this->bugherdApi->getTasks($connection->getBugherdProject(), ['external_id' => $jira_key]);
      if (!empty($response['tasks'])) {
        $task = reset($response['tasks']);
        $task['project_id'] = $connection->getBugherdProject();
        return $task;
      }
    }
    return FALSE;
  }

  /**
   * Get the original Jira Issue key if the issue has moved.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return string
   *   Original issue key.
   */
  protected function getOriginalJiraKey(array $jira_data) {
    if (!empty($jira_data['changelog']['items'])) {
      foreach ($jira_data['changelog']['items'] as $change) {
        if ($change['field'] == 'Key') {
          return $change['fromString'];
        }
      }
    }
    return $this->getCurrentJiraKey($jira_data);
  }

  /**
   * Get the Current Jira Issue key.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return string
   *   Issue key.
   */
  protected function getCurrentJiraKey(array $jira_data) {
    return $jira_data['issue']['key'];
  }

  /**
   * Get the original Jira project if the issue has moved.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return string
   *   Jira project key.
   */
  protected function getOriginalJiraProject(array $jira_data) {
    if (!empty($jira_data['changelog']['items'])) {
      foreach ($jira_data['changelog']['items'] as $change) {
        if ($change['field'] == 'project') {
          $project = $this->jiraApi->getCommunicationService()
            ->get('/rest/api/latest/project/' . $change['from']);
          return $project->key;
        }
      }
    }

    return $this->getCurrentJiraProject($jira_data);
  }

  /**
   * Get the current Jira project form the given Jira webhook data.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return string
   *   Jira project key.
   */
  protected function getCurrentJiraProject(array $jira_data) {
    return $jira_data['issue']['fields']['project']['key'];
  }

}
