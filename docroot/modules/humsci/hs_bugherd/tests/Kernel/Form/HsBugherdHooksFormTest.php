<?php

namespace Drupal\Tests\hs_bugherd\Kernel;

use biologis\JIRA_PHP_API\GuzzleCommunicationService;
use biologis\JIRA_PHP_API\IssueService;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\hs_bugherd\Form\HsBugherdHooksForm;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;
use Prophecy\Argument;

/**
 * Class HsBugherdFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\HsBugherdHooksForm
 * @group hs_bugherd
 */
class HsBugherdHooksFormTest extends KernelTestBase {

  const PROJECT_ID = '123456789';

  /**
   * Key entity.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $key;

  /**
   * {@inheritdo}
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
   * {@inheritdo}
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
   * Test the webhooks form functionality.
   */
  public function testBugherdHooksForm() {
    $form_object = HsBugherdHooksForm::create($this->container);
    $this->assertEquals('hs_bugherd_hooks', $form_object->getFormId());
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $this->assertCount(4, Element::children($form));
    $this->assertArrayHasKey('jira', $form['hooks']);
    $this->assertArrayHasKey('bugherd', $form['hooks']);
    $this->assertEmpty($form['hooks']['jira']['hooks']['#markup']);
    $this->assertEmpty($form['hooks']['bugherd']['hooks']['#markup']);

    $form_object->submitForm($form, $form_state);

    $this->setServices(TRUE);
    $form_object = HsBugherdHooksForm::create($this->container);
    $form = $form_object->buildForm($form, $form_state);
    $this->assertEquals('jira:issue_updated; comment_created: http://example.com', $form['hooks']['jira']['hooks']['#markup']);
    $this->assertEquals('comment: http://example.com', $form['hooks']['bugherd']['hooks']['#markup']);

    $form_object->submitForm($form, $form_state);

    $this->config('bugherdapi.settings')->delete();
    $form = $form_object->buildForm($form, $form_state);
    $this->assertCount(0, Element::children($form));
    $this->assertEquals('Bugherd or Jira has not been configured.', $form['#markup']->render());
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

  protected $withHooks;

  public function __construct($with_hooks) {
    $this->withHooks = $with_hooks;
  }

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

  protected $withHooks;

  public function __construct($jiraURL, array $jiraCredentials, $with_hooks) {
    parent::__construct($jiraURL, $jiraCredentials);
    $this->withHooks = $with_hooks;
  }

  public function post($path, \stdClass $data, $expectedStatusCode = 200) {

  }

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

  public function put($path, \stdClass $data) {

  }

  public function delete($path, $parameters = []) {

  }

}
