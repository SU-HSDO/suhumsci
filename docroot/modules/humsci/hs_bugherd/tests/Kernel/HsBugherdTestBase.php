<?php

namespace Drupal\Tests\hs_bugherd\Kernel;

use biologis\JIRA_PHP_API\GuzzleCommunicationService;
use biologis\JIRA_PHP_API\IssueService;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;
use Prophecy\Argument;

/**
 * Class HsBugherdTestBase.
 *
 * @package Drupal\Tests\hs_bugherd\Kernel
 */
abstract class HsBugherdTestBase extends KernelTestBase {

  const PROJECT_ID = '123456789';

  /**
   * Key entity.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $key;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'hs_bugherd',
    'bugherdapi',
    'jira_rest',
    'encrypt',
    'key',
  ];

  /**
   * {@inheritdoc}
   *
   * Disable strict config since bugherdapi module doesn't have schema file.
   *
   * @see https://www.drupal.org/project/bugherdapi/issues/2999180
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->key = Key::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_input' => 'text_field',
      'key_provider_settings' => ['key_value' => $this->randomString()],
    ]);
    $this->key->save();

    $this->config('bugherdapi.settings')
      ->set('project_id', self::PROJECT_ID)
      ->set('api_key', $this->key->id())
      ->set('jira_project', 'TEST')
      ->set('status_map', [])
      ->save();

    $this->setServices();
  }

  /**
   * Set Drupal service containers.
   *
   * @param bool $with_hooks
   *   Set the services with valid hook responses.
   */
  protected function setServices($with_hooks = FALSE) {
    $this->container->set('hs_bugherd', $this->getBugherdService($with_hooks));
    $this->container->set('jira_rest_wrapper_service', $this->getJiraService($with_hooks));
  }

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
      rand(1000, 9999) => $this->randomString(),
    ]);
    $bugherd_api->setApiKey(Argument::type('string'))->willReturn();
    $bugherd_api->getOrganization()->willReturn([
      'organization' => [
        'id' => 999,
        'name' => $this->randomString(),
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
