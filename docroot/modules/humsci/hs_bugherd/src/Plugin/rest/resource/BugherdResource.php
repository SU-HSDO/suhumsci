<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\key\Entity\Key;
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
   * Responds to POST requests.
   *
   * @param array $data
   *   Post data from API.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post(array $data) {
    try {
      // Data from jira has this key. Bugherd does not.
      if (isset($data['webhookEvent'])) {
        return new ResourceResponse($this->sendToBugherd($data));
      }
      return new ResourceResponse($this->sendToJira($data));
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new ResourceResponse($e->getMessage());
    }
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
   * @throws \Exception
   */
  protected function sendToJira(array $data) {

    // Bugherd commented.
    if (isset($data['comment'])) {
      $task = $data['comment']['task'];
      unset($data['comment']['task']);
      return $this->bugherdTaskCommented($data['comment'], $task);
    }

    // Bugherd task created or updated.
    return $this->bugherdTaskUpdated($data['task']);
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
      $new_issue_created = TRUE;
      $issue = $this->createJiraIssue($task);

      $this->logger->info('New JIRA issue from bugherd. Jira: @jira, Bugherd: @bugherd', [
        '@jira' => $issue->getKey(),
        '@bugherd' => $task['local_task_id'],
      ]);
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
   * @param null $name
   *   Desired name of the file.
   *
   * @return mixed
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

    // Initiating CURLFile for preparing the upload
    $cfile = new \CURLFile($path);
    $cfile->setPostFilename($file_name);

    // Creating array for Curl Post Fields.
    $data = ['file' => $cfile];
    $ch = curl_init();
    // Setting the headers.
    $headers = [
      'X-Atlassian-Token: nocheck',
      'Content-Type: multipart/form-data',
    ];

    curl_setopt_array($ch, [
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

    $response = curl_exec($ch);
    curl_close($ch);
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
        throw new \Exception(t('JIRA comment could not be created for task # @bugherd', ['@bugherd' => $task['local_task_id']]));
      }

      return $issue->getKey();
    }

    throw new \Exception(t('Unable to find JIRA ticket for task # @bugherd', ['@bugherd' => $task['local_task_id']]));
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
    $issue->fields->issuetype->setId('1');
    $issue->fields->addGenericJiraObject('priority');
    $issue->fields->priority->setId('4');
    $issue->fields->setSummary($this->getTaskName($task));
    $issue->fields->addGenericJiraObject('reporter');
    $issue->fields->reporter->setName('');

    if (strpos($task['requester']['email'], 'stanford.edu') !== FALSE) {
      $requester_sunet = substr($task['requester']['email'], 0, strpos($task['requester']['email'], '@'));

      // Search Jira for the users that match the username.
      $jira_users = $this->jiraIssueService->getCommunicationService()
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

    $issue->save();

    // Now that the JIRA issue is created, link it to the bugherd item.
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
    $description[] = "Requester: {$task['requester']['display_name']}";
    $description[] = "URL: {$task['site']}{$task['url']}";
    $description[] = "Browser: {$task['requester_browser']}";
    $description[] = "Browser size: {$task['requester_browser_size']}";
    $description[] = "Browser size: {$task['requester_resolution']}";
    $description[] = "Item: " . strip_tags($task['selector_info']['html'], '<img><a><p><iframe>');

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
      $this->logger->info('Unable to find bugherd ticket for issue: @key', ['@key' => $issue_key]);
      return FALSE;
    }

    if (isset($data['comment']) && !$this->isNewBugherdComment($bugherd_task, $data['comment'])) {
      $this->logger->info('Comment rejected from @name. Comment is not new.', ['@name' => $data['comment']['author']['name']]);
      return $this->t('Comment rejected from @name', ['@name' => $data['comment']['author']['name']]);
    }

    switch ($data['webhookEvent']) {
      case 'comment_created':
        $comment = [
          'text' => $data['comment']['author']['displayName'] . ': ' . $data['comment']['body'],
        ];
        return $this->bugherdApi->addComment($bugherd_task['id'], $comment);

      case 'jira:issue_updated':
        if ($data['changelog']['items'][0]['field'] == 'status') {
          $jira_status = $data['changelog']['items'][0]['to'];
          if ($new_status = $this->getTranslatedStatus($jira_status)) {
            $status = ['status' => $new_status];
            return $this->bugherdApi->updateTask($bugherd_task['id'], $status);
          }
          $this->logger->info(t('Unable to map Jira status to bugherd. Jira status id: @jira'), ['@jira' => $jira_status]);
        }
    }

    return FALSE;
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
   *   New comment data from JIRA
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

    foreach ($status_map as $bugherd_id => $ids) {
      $ids = explode(',', $ids);

      foreach ($ids as &$id) {
        $id = trim($id);
      }

      if (in_array($status, $ids)) {
        return $bugherd_id;
      }
    }
    $status_map = array_flip($status_map);
    return isset($status_map[$status]) ? $status_map[$status] : NULL;
  }

}
