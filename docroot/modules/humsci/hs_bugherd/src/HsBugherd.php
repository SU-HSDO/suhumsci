<?php

namespace Drupal\hs_bugherd;

use Bugherd\Client;

/**
 * Class BugherdApi.
 *
 * @package Drupal\bugherdapi
 */
class HsBugherd {

  const BUGHERDAPI_ORGANIZATION = 'organization';

  const BUGHERDAPI_USER = 'user';

  const BUGHERDAPI_PROJECT = 'project';

  const BUGHERDAPI_TASK = 'task';

  const BUGHERDAPI_COMMENT = 'comment';

  const BUGHERDAPI_WEBHOOK = 'webhook';

  const BUGHERDAPI_BACKLOG = 0;

  const BUGHERDAPI_TODO = 1;

  const BUGHERDAPI_DOING = 2;

  const BUGHERDAPI_DONE = 4;

  const BUGHERDAPI_CLOSED = 5;

  /**
   * The Bugherd api key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * @var \Bugherd\Client
   */
  protected $client;

  /**
   * HsBugherd constructor.
   */
  public function __construct() {
    $this->apiKey = static::getApiKey();
    $this->projectKey = self::getProjectId();
    $this->client = new Client($this->apiKey);
  }

  /**
   * Get the configured Bugherd API key.
   *
   * @return string|null
   *   API key.
   */
  public static function getApiKey() {
    return \Drupal::configFactory()->get('bugherdapi.settings')->get('api_key');
  }

  /**
   * @param string $key
   */
  public function setApiKey($api_key) {
    $this->apiKey = $api_key;
    // Rebuild the client
    $this->client = new Client($this->apiKey);
  }

  /**
   * @return array|mixed|null
   */
  public static function getProjectId() {
    return \Drupal::configFactory()
      ->get('bugherdapi.settings')
      ->get('project_id');
  }

  /**
   * @param string $project_id
   */
  public function setProjectId($project_id) {
    $this->projectKey = $project_id;
  }

  /**
   * Get the desired API.
   *
   * @return \Bugherd\Api\AbstractApi
   *   The api.
   */
  protected function getApi($api) {
    return $this->client->api($api);
  }

  /**
   * Get information about the Bugherd Org.
   *
   * @return array
   *   Returned response.
   */
  public function getOrganization() {
    return $this->getApi(self::BUGHERDAPI_ORGANIZATION)->show();
  }

  /**
   * Get a list of all members and guests in the organization.
   *
   * @param bool $members
   *   Get the Members.
   * @param bool $guests
   *   Get the Guests
   *
   * @return array
   *   Returned response.
   */
  public function getUsers($members = TRUE, $guests = TRUE) {
    if ($guests && $members) {
      return $this->getApi(self::BUGHERDAPI_USER)->all();
    }
    if ($members) {
      return $this->getApi(self::BUGHERDAPI_USER)->getMembers();
    }
    if ($guests) {
      return $this->getApi(self::BUGHERDAPI_USER)->getGuests();
    }
    return [];
  }

  /**
   * Get a list of all projects in the organization.
   *
   * @return array
   *   Returned response.
   */
  public function getProjects() {
    return $this->getApi(self::BUGHERDAPI_PROJECT)->listing(FALSE, FALSE);
  }

  /**
   * Get all task for a project.
   *
   * @param integer $project_id
   *   Project id found from getProjects().
   *
   * @return array
   *   Returned response.
   */
  public function getTasks($project_id = NULL) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->all($project_id ?: $this->projectKey);
  }

  public function getTask($task_id, $project_id = NULL) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->show($project_id ?: $this->projectKey, $task_id);
  }

  /**
   * @param string $project_id
   * @param int $task_id
   * @param array $data
   *
   * @return mixed
   */
  public function updateTask($project_id, $task_id, $data) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->update($project_id, $task_id, $data);
  }

  /**
   * Get all the commments on a particular task.
   *
   * @param integer $projectId
   *   Project id found from getProjects().
   * @param integer $taskId
   *   Task id found from getTasks().
   *
   * @return array
   *   Returned response.
   */
  public function getComments($projectId, $taskId) {
    return $this->getApi(self::BUGHERDAPI_COMMENT)->all($projectId, $taskId);
  }

  /**
   * Get a list of all the hooks in the organization.
   *
   * @return array
   *   Returned response.
   */
  public function getHooks() {
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->all();
  }

  /**
   *
   *
   * @param array $params
   *
   * @return array
   *   Returned response.
   *
   * @throws \Exception
   */
  public function createWebhook(array $params) {
    if (empty($params['event'])) {
      throw new \Exception('Event is required by the API');
    }
    $params += ['target_url' => ''];
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->create($params);
  }

  /**
   * Delete a specific webook.
   *
   * @param $id
   *   Webhook id found from getHooks().
   *
   * @return mixed
   *   Returned response.
   */
  public function deleteWebhook($id) {
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->remove($id);
  }

}
