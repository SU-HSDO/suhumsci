<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use biologis\JIRA_PHP_API\Issue;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\key\Entity\Key;
use Drupal\rest\ResourceResponse;

/**
 * Class BugherdResource.
 *
 * API for bugherd webhooks to collect data for Jira tickets.
 *
 * @RestResource(
 *   id = "hs_bugherd_resource_bugherd",
 *   label = @translation("HS Bugherd Resource Bugherd"),
 *   uri_paths = {
 *     "canonical" = "/api/hs-bugherd/bugherd",
 *     "https://www.drupal.org/link-relations/create" = "/api/hs-bugherd/bugherd"
 *   }
 * )
 */
class BugherdResource extends HsBugherdResourceBase {

  /**
   * Responds to POST requests.
   *
   * @param array $bugherd_data
   *   Post data from API.
   *
   * @return \Drupal\rest\ResourceResponse
   *   API response.
   *
   * @see https://www.bugherd.com/api_v2
   */
  public function post(array $bugherd_data) {
    $task = $bugherd_data['task'] ?: $bugherd_data['comment']['task'];
    $this->setBugherdConnection($task['project_id']);
    if (!$this->bugherdConnection) {
      return new ResourceResponse($this->t('No connection data'));
    }

    // New bugherd task.
    if (empty($task['external_id'])) {
      $issue = $this->createJiraIssue($task);
      $data = ['external_id' => $issue->getKey()];
      $this->bugherdApi->updateTask($task['id'], $data, $task['project_id']);
      $response = new ResourceResponse($issue->getKey());
    }
    else {
      $issue = $this->jiraApi->load($task['external_id']);
      if (!empty($bugherd_data['comment'])) {
        $this->addJiraComment($task, $issue, $bugherd_data['comment']);
        $response = new ResourceResponse($this->t('Comment added to Jira ticket @key', ['@key' => $issue->getKey()]));
      }
      else {
        // Bugherd task updated or status was changed.
        $issue->fields->setDescription($this->getDescription($task));
        $issue->fields->setSummary($this->getTaskName($task));
        $issue->save();

        // Attach the screenshot to the Jira Issue if it doesn't have any
        // already.
        if (!empty($task['screenshot_url']) && empty($issue->fields->attachment)) {
          $this->addAttachment($issue->getKey(), $task['screenshot_url']);
        }

        $response = new ResourceResponse($this->t('Jira issue updated @key', ['@key' => $issue->getKey()]));
      }
    }

    $response->setMaxAge(0);
    $build = ['#cache' => ['max-age' => 0]];
    $response->addCacheableDependency($build);
    return $response;
  }

  /**
   * Set the appropriate Bugherd connection entity.
   *
   * @param int $bugherd_project
   *   Bugherd Project ID.
   */
  protected function setBugherdConnection($bugherd_project) {
    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach (BugherdConnection::loadMultiple() as $connection) {
      if ($connection->getBugherdProject() == $bugherd_project) {
        $this->bugherdConnection = $connection;
      }
    }
  }

  /**
   * Create a new Jira issue from Bugherd webhook data.
   *
   * @param array $bugherd_task
   *   Bugherd webhook data.
   *
   * @return \biologis\JIRA_PHP_API\Issue
   *   Created Jira issue.
   */
  protected function createJiraIssue(array $bugherd_task) {
    /** @var \biologis\JIRA_PHP_API\Issue $issue */
    $issue = $this->jiraApi->create();
    $issue->fields->project->setKey($this->bugherdConnection->getJiraProject());
    $issue->fields->setDescription($this->getDescription($bugherd_task));
    $issue->fields->issuetype->setId('1');
    $issue->fields->addGenericJiraObject('priority');
    $issue->fields->priority->setId('4');
    $issue->fields->setSummary($this->getTaskName($bugherd_task));
    $issue->fields->addGenericJiraObject('reporter');
    $issue->fields->reporter->setName('');
    if (strpos($bugherd_task['requester']['email'], 'stanford.edu') !== FALSE) {
      $requester_sunet = substr($bugherd_task['requester']['email'], 0, strpos($bugherd_task['requester']['email'], '@'));
      // Search Jira for the users that match the username.
      $jira_users = $this->jiraApi->getCommunicationService()
        ->get('user/search?', ['username' => $requester_sunet]) ?: [];
      // Since the search isn't an exact match search, we want to loop through
      // and make sure we have the right user's name.
      foreach ($jira_users as $user) {
        if ($user->name == $requester_sunet) {
          $issue->fields->reporter->setName($requester_sunet);
          break;
        }
      }
    }
    $issue->save();
    return $issue;
  }

  /**
   * Add a Bugherd comment to a Jira issue.
   *
   * @param array $bugherd_task
   *   Bugherd task data.
   * @param \biologis\JIRA_PHP_API\Issue $jira_issue
   *   Issue that matches the Bugherd task.
   * @param array $comment
   *   Comment data from webhook.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|bool
   *   Result of adding comment.
   */
  protected function addJiraComment(array $bugherd_task, Issue $jira_issue, array $comment) {
    // Block comments from anonymous users so we don't have looping APIs.
    if (empty($comment['user']['email'])) {
      return $this->t('Comment rejected from Anonymous');
    }

    // Add the comment now.
    if ($result = $jira_issue->addComment($comment['text'])) {
      $this->logger->info('New comment sent to JIRA issue from @name. Jira: @jira, Bugherd: @bugherd', [
        '@name' => $comment['user']['display_name'],
        '@jira' => $jira_issue->getKey(),
        '@bugherd' => $bugherd_task['local_task_id'],
      ]);
    }
    return $result;
  }

  /**
   * Create a usable description for JIRA from bugherd data.
   *
   * @param array $bugherd_task
   *   Bugherd task data.
   *
   * @return string
   *   Built description.
   */
  protected function getDescription(array $bugherd_task) {
    $description = [];
    $description[] = $bugherd_task['description'];
    $description[] = '';
    $description[] = "Requester: {$bugherd_task['requester']['display_name']}";
    $description[] = "URL: {$bugherd_task['site']}{$bugherd_task['url']}";
    $description[] = "Browser: {$bugherd_task['requester_browser']}";
    $description[] = "Browser size: {$bugherd_task['requester_browser_size']}";
    $description[] = "Browser size: {$bugherd_task['requester_resolution']}";
    if (!empty($bugherd_task['selector_info']['html'])) {
      $description[] = "Item: " . strip_tags($bugherd_task['selector_info']['html'], '<img><a><p><iframe>');
    }
    if ($bugherd_task['screenshot_url']) {
      $description[] = "Screenshot: {$bugherd_task['screenshot_url']}";
    }
    return implode(PHP_EOL, $description);
  }

  /**
   * Get the configured task name to be created in JIRA.
   *
   * @param array $bugherd_task
   *   Bugherd task data.
   *
   * @return string
   *   Constructed name.
   */
  protected function getTaskName(array $bugherd_task) {
    // Trim down the descrption to only 7 words so we dont clutter up JIRA with
    // a long paragraph as the summary. Also Jira doesnt like new lines in the
    // summary.
    $description_words = explode(' ', trim(preg_replace("/\r|\n/", "", $bugherd_task['description'])));
    $title = array_slice($description_words, 0, 7);
    $title = implode(' ', $title);
    $title .= count($description_words) > 7 ? '...' : '';
    return "BUGHERD-{$bugherd_task['local_task_id']}: $title";
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
    $jira_config = $this->configFactory->get('jira_rest.settings');
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

}
