<?php

namespace Drupal\hs_bugherd;

use Bugherd\Client;
use Drupal\jira_rest\JiraRestWrapperService;

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
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $jiraRestWrapper;

  /**
   * BugherdApi constructor.
   */
  public function __construct(JiraRestWrapperService $jira_rest) {
    $this->jiraRestWrapper = $jira_rest;
    $this->apiKey = static::getApiKey();
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
    return $this->getApi(self::BUGHERDAPI_PROJECT)->all();
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
  public function getTasks($project_id) {
    return $this->getApi(self::BUGHERDAPI_TASK)->all($project_id);
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
