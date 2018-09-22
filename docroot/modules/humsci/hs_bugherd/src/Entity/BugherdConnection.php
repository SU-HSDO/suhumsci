<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\hs_bugherd\HsBugherd;
use Drupal\key\Entity\Key;

/**
 * Defines the Bugherd Connection entity.
 *
 * @ConfigEntityType(
 *   id = "bugherd_connection",
 *   label = @Translation("Bugherd Connection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hs_bugherd\BugherdConnectionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hs_bugherd\Form\BugherdConnectionForm",
 *       "edit" = "Drupal\hs_bugherd\Form\BugherdConnectionForm",
 *       "delete" = "Drupal\hs_bugherd\Form\BugherdConnectionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bugherd",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/bugherd/{bugherd_connection}",
 *     "add-form" = "/admin/config/services/bugherd/add",
 *     "edit-form" = "/admin/config/services/bugherd/{bugherd_connection}/edit",
 *     "delete-form" = "/admin/config/services/bugherd/{bugherd_connection}/delete",
 *     "collection" = "/admin/config/services/bugherd"
 *   }
 * )
 */
class BugherdConnection extends BugherdConnectionBase {

  /**
   * {@inheritdoc}
   */
  public function updateJiraTicket(array $bugherd_data) {
    // New Bugherd comment.
    if (isset($bugherd_data['comment'])) {
      $task = $bugherd_data['comment']['task'];
      unset($bugherd_data['comment']['task']);
      return $this->bugherdTaskCommented($bugherd_data['comment'], $task);
    }

    // Bugherd task created or updated.
    return $this->bugherdTaskUpdated($bugherd_data['task']);
  }

  /**
   * {@inheritdoc}
   */
  public function updateBugherdTicket(array $jira_data) {
    $issue_key = $jira_data['issue']['key'];
    if (!($bugherd_task = $this->getBugherdTask($issue_key))) {
      return FALSE;
    }

    if (isset($jira_data['comment']) && !$this->isNewBugherdComment($bugherd_task, $jira_data['comment'])) {
      $this->logger->info('Comment rejected from @name. Comment is not new.', ['@name' => $jira_data['comment']['author']['name']]);
      return $this->t('Comment rejected from @name', ['@name' => $jira_data['comment']['author']['name']]);
    }

    $return = FALSE;
    switch ($jira_data['webhookEvent']) {
      case 'comment_created':
        $comment = [
          'text' => $jira_data['comment']['author']['displayName'] . ': ' . $jira_data['comment']['body'],
        ];
        $return = $this->bugherdApi->addComment($bugherd_task['id'], $comment);
        break;

      case 'jira:issue_updated':
        if ($new_status = $this->getNewBugherdStatus($jira_data)) {
          $updates = [
            'status' => $new_status,
            'external_id' => $jira_data['issue']['key'],
          ];
          $return = $this->bugherdApi->updateTask($bugherd_task['id'], $updates);
        }
        break;
    }

    return $return;
  }

  /**
   * Get the new bugherd status from the Jira webhook data.
   *
   * @param array $jira_data
   *   Jira webhook data.
   *
   * @return null|string
   *   The appropriate bugherd status.
   */
  protected function getNewBugherdStatus(array $jira_data) {
    $changelog = $jira_data['changelog']['items'];

    foreach ($changelog as $change) {
      if ($change['field'] == 'status') {
        $jira_status = $change['to'];
        return $this->getBugherdStatus($jira_status);
      }
    }
  }

  /**
   * Check if the bugherd task has a comment that matches the new comment.
   *
   * When a comment is added in bugherd, it sends the data to JIRA. Jira then
   * fires it's webhook and attempts to send the data back to Bugherd. This
   * would cause duplicate comments. So we check if any comment in bugherd
   * has the same text as the new comment from JIRA.
   *
   * @param array $bugherd_task
   *   Bugherd task matching the Jira issue.
   * @param array $new_comment
   *   New comment data from JIRA.
   *
   * @return bool
   *   If the comment already exists in Bugherd.
   */
  protected function isNewBugherdComment(array $bugherd_task, array $new_comment) {
    $comments = $this->bugherdApi->getComments($bugherd_task['id']);
    foreach ($comments['comments'] as $comment) {
      if (strpos($new_comment['body'], $comment['text']) !== FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * A task was either created or updated, find/create jira issue and update.
   *
   * @param array $task
   *   Bugherd API data.
   *
   * @return string
   *   Jira issue id.
   *
   * @throws \Exception
   */
  protected function bugherdTaskUpdated(array $task) {
    $new_issue_created = FALSE;
    // Create a jira issue if none exists.
    if (empty($issue = $this->getJiraIssue($task['external_id']))) {
      if ($issue = $this->createJiraIssue($task)) {

        $this->logger->info('New JIRA issue from bugherd. Jira: @jira, Bugherd: @bugherd', [
          '@jira' => $issue->getKey(),
          '@bugherd' => $task['local_task_id'],
        ]);
      }
    }

    // Issue needs to be updated.
    if (!$new_issue_created) {
      $issue->fields->setDescription($this->buildDescription($task));
      $issue->save();

      if ($task['status'] == HsBugherd::BUGHERDAPI_CLOSED) {
        $issue->addComment($this->t('Issue closed in Bugherd by @name', ['@name' => $task['updater']['display_name']]));
      }
    }

    // Attach the screenshot to the Jira Issue if it doesn't have any already.
    if (!empty($task['screenshot_url']) && empty($issue->fields->attachment)) {
      $this->addAttachment($issue->getKey(), $task['screenshot_url']);
    }

    return $issue->getKey();
  }

  /**
   * Add an attachment file to a JIRA issue.
   *
   * The method Drupal\jira_rest\JiraRestWrapperService::attachFileToIssueByKey
   * doesn't work correctly. This provides our own method to attach the file.
   * https://www.drupal.org/project/jira_rest/issues/2982894 is the issue.
   *
   * @param string $issueKey
   *   Jira Issue ID.
   * @param string $file
   *   Url to file.
   * @param string|null $name
   *   Desired name of the file.
   *
   * @return mixed
   *   Response from Jira API.
   *
   * @see https://community.atlassian.com/t5/Jira-questions/Upload-URL-Attachment-via-API-on-JIRA-PHP/qaq-p/44828
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/?_ga=2.240914371.1127742332.1530222923-1785182880.1526662104#api-api-2-issue-issueIdOrKey-attachments-post
   */
  protected function addAttachment($issueKey, $file, $name = NULL) {
    $jira_config = \Drupal::configFactory()->get('jira_rest.settings');
    $url = $jira_config->get('jira_rest.instanceurl');
    $username = $jira_config->get('jira_rest.username');
    /** @var \Drupal\key\Entity\Key $key */
    $key = Key::load($jira_config->get('jira_rest.password'));
    $password = $key ? $key->getKeyValue() : '';

    // Creating file name.
    $file_name = $name ? $name : time() . '-' . basename($file);
    $path = tempnam(sys_get_temp_dir(), 'bugherd');

    // Saving file in a temp path.
    file_put_contents($path, file_get_contents($file));

    // Initiating CURLFile for preparing the upload.
    $cfile = new \CURLFile($path);
    $cfile->setPostFilename($file_name);

    // Creating array for Curl Post Fields.
    $data = ['file' => $cfile];
    $curl = curl_init();
    // Setting the headers.
    $headers = [
      'X-Atlassian-Token: nocheck',
      'Content-Type: multipart/form-data',
    ];

    curl_setopt_array($curl, [
      CURLOPT_URL => "$url/rest/api/latest/issue/$issueKey/attachments",
      CURLOPT_USERPWD => "$username:$password",
      CURLOPT_POST => 1,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_VERBOSE => 1,
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    unlink($path);

    return $response;
  }

  /**
   * Add a comment to JIRA from bugherd.
   *
   * @param array $comment
   *   Bugherd Comment data.
   * @param array $task
   *   Bugherd Task data.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Updated issue or message.
   *
   * @throws \Exception
   */
  protected function bugherdTaskCommented(array $comment, array $task) {
    // Block comments from anonymous users so we don't have looping APIs.
    if (empty($comment['user']['email'])) {
      $this->logger->info('Anonymous comment rejected for ticket @id', ['@id' => $task['local_task_id']]);
      return $this->t('Comment rejected from Anonymous');
    }

    if ($issue = $this->getJiraIssue($task['external_id'])) {
      // Add the comment now.
      if ($issue->addComment($comment['text'])) {
        $this->logger->info('New comment sent to JIRA issue from @name. Jira: @jira, Bugherd: @bugherd', [
          '@name' => $comment['user']['display_name'],
          '@jira' => $issue->getKey(),
          '@bugherd' => $task['local_task_id'],
        ]);
      }
      else {
        throw new \Exception(t('JIRA comment could not be created for task #@bugherd', ['@bugherd' => $task['local_task_id']]));
      }

      return $issue->getKey();
    }

    throw new \Exception(t('Unable to find JIRA ticket for task #@bugherd', ['@bugherd' => $task['local_task_id']]));
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
    $issue = $this->jiraApi->getIssueService()->create();
    $issue->fields->project->setKey($this->jiraProject);
    $issue->fields->setDescription($this->buildDescription($task));
    $issue->fields->issuetype->setId('1');
    $issue->fields->addGenericJiraObject('priority');
    $issue->fields->priority->setId('4');
    $issue->fields->setSummary($this->getTaskName($task));
    $issue->fields->addGenericJiraObject('reporter');
    $issue->fields->reporter->setName('');

    if (strpos($task['requester']['email'], 'stanford.edu') !== FALSE) {
      $requester_sunet = substr($task['requester']['email'], 0, strpos($task['requester']['email'], '@'));

      // Search Jira for the users that match the username.
      $jira_users = $this->jiraApi->getIssueService()->getCommunicationService()
        ->get('user/search?', ['username' => $requester_sunet]);

      // Since the search isn't an exact match search, we want to loop through
      // and make sure we have the right user's name.
      foreach ($jira_users as $user) {
        if ($user->name == $requester_sunet) {
          $issue->fields->reporter->setName($requester_sunet);
          break;
        }
      }
    }
    if ($issue->save()) {
      // Now that the JIRA issue is created, link it to the bugherd item.
      $data = ['external_id' => $issue->getKey()];
      $this->bugherdApi->updateTask($task['id'], $data, $this->getBugherdProject());
      return $issue;
    }
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
    $description[] = "Requester: {$task['requester']['display_name']}";
    $description[] = "URL: {$task['site']}{$task['url']}";
    $description[] = "Browser: {$task['requester_browser']}";
    $description[] = "Browser size: {$task['requester_browser_size']}";
    $description[] = "Browser size: {$task['requester_resolution']}";
    if (!empty($task['selector_info']['html'])) {
      $description[] = "Item: " . strip_tags($task['selector_info']['html'], '<img><a><p><iframe>');
    }

    if ($task['screenshot_url']) {
      $description[] = "Screenshot: {$task['screenshot_url']}";
    }
    return implode(PHP_EOL, $description);
  }

}
