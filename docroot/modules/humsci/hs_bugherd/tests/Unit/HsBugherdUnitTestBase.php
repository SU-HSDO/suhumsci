<?php

namespace Drupal\Tests\hs_bugherd\Unit;

use biologis\JIRA_PHP_API\GuzzleCommunicationService;
use biologis\JIRA_PHP_API\IssueService;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\Tests\Core\Form\FormTestBase;
use Prophecy\Argument;

/**
 * Class HsBugherdUnitTestBase.
 */
abstract class HsBugherdUnitTestBase extends FormTestBase {

  /**
   * @param bool $with_hooks
   *
   * @return object
   */
  protected function getBugherdService($with_hooks = FALSE) {
    $bugherd_api = $this->prophesize(HsBugherd::class);
    if ($with_hooks) {
      $bugherd_api->getHooks()->willReturn([
        'webhooks' => [
          [
            'id' => 99999,
            'target_url' => 'http://example.com',
            'event' => 'comment',
            'project_id' => self::PROJECT_ID,
          ],
        ],
      ]);
    }
    else {
      $bugherd_api->getHooks()->willReturn([]);
    }
    $bugherd_api->deleteWebhook(Argument::type('integer'))->willReturn();
    $bugherd_api->createWebhook(Argument::any())
      ->willThrow(new \Exception('This failed!'));
    $bugherd_api->isConnectionSuccessful()->willReturn(TRUE);
    $bugherd_api->getProjects()->willReturn([
      9999 => 'Test Project',
    ]);
    $bugherd_api->setApiKey(Argument::type('string'))->willReturn();
    $bugherd_api->getOrganization()->willReturn([
      'organization' => [
        'id' => 999,
        'name' => 'TEST',
      ],
    ]);
    return $bugherd_api->reveal();
  }

  /**
   * @return object
   */
  protected function getJiraService($with_hooks = FALSE) {
    $jira_wrapper = $this->prophesize(JiraRestWrapperService::class);
    $jira_wrapper->getIssueService()
      ->willReturn(new JiraIssueServiceTest($with_hooks));
    return $jira_wrapper->reveal();
  }

}

/**
 * Class JiraIssueServiceTest.
 *
 * @package Drupal\Tests\hs_bugherd\Kernel
 */
class JiraIssueServiceTest extends IssueService {

  /**
   * Get the service with valid webhooks.
   *
   * @var bool
   */
  protected $withHooks;

  /**
   * {@inheritdoc}
   */
  public function __construct($with_hooks) {
    $this->withHooks = $with_hooks;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommunicationService() {
    return new JiraCommunicationServiceTest('', [], $this->withHooks);
  }
}

/**
 * Class JiraCommunicationServiceTest.
 *
 * @package Drupal\Tests\hs_bugherd\Kernel
 */
class JiraCommunicationServiceTest extends GuzzleCommunicationService {

  /**
   * Get the service with valid webhooks.
   *
   * @var bool
   */
  protected $withHooks;

  /**
   * {@inheritdoc}
   */
  public function __construct($jiraURL, array $jiraCredentials, $with_hooks) {
    parent::__construct($jiraURL, $jiraCredentials);
    $this->withHooks = $with_hooks;
  }

  /**
   * {@inheritdoc}
   */
  public function post($path, \stdClass $data, $expectedStatusCode = 200) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($path, $paramaters = []) {
    if (!$this->withHooks) {
      return [];
    }
    $hook = [
      'name' => 'test hook',
      'url' => 'http://example.com',
      'excludeBody' => FALSE,
      'enabled' => TRUE,
      'self' => 'https://stanfordits.atlassian.net/rest/webhooks/1.0/webhook/999999',
      'events' => ['jira:issue_updated', 'comment_created'],
      'filters' => (object) [
        'issue-related-events-section' => "project = TEST and summary ~ 'BUGHERD-*'",
      ],
    ];
    if ($path == '/rest/webhooks/1.0/webhook') {
      return [(object) $hook];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function put($path, \stdClass $data) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path, $parameters = []) {
  }
}