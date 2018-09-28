<?php

namespace Drupal\Tests\hs_bugherd\Unit;

use biologis\JIRA_PHP_API\GuzzleCommunicationService;
use biologis\JIRA_PHP_API\IssueService;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\key\Entity\Key;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

if (!defined('SAVED_NEW')) {
  define('SAVED_NEW', 1);
}
if (!defined('SAVED_UPDATED')) {
  define('SAVED_UPDATED', 2);
}

/**
 * Class HsBugherdUnitTestBase.
 */
abstract class HsBugherdUnitTestBase extends UnitTestCase {

  /**
   * @var \Drupal\key\Entity\Key
   */
  protected $key;

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeRepository;

  /**
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $configEntityStorage;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->key = new Key([
      'id' => $this->randomMachineName(),
      'label' => $this->getRandomGenerator()->string(),
    ], 'key');

    $this->container = new ContainerBuilder();

    $this->configFactory = $this->createMock(ConfigFactory::class);
    $this->configFactory->method('get')
      ->with('hs_bugherd.connection_settings')
      ->willReturn('stuff');
    $this->container->set('config.factory', $this->configFactory);

    $this->container->set('string_translation', $this->createMock(TranslationManager::class));

    $this->configEntityStorage = $this->createMock(ConfigEntityStorage::class);
    $this->configEntityStorage->method('loadMultiple')
      ->will($this->returnCallback([$this, 'configEntityStorageCallback']));

    $this->entityTypeRepository = $this->createMock(EntityTypeRepository::class);
    $this->entityTypeRepository->method('getEntityTypeFromClass')
      ->will($this->returnCallback([$this, 'entityTypeRepositoryCallback']));
    $this->container->set('entity_type.repository', $this->entityTypeRepository);

    $this->entityTypeManager = $this->createMock(EntityTypeManager::class);
    $this->entityTypeManager->method('getStorage')
      ->withAnyParameters()
      ->willReturn($this->configEntityStorage);
    $this->entityTypeManager->method('getDefinition')
      ->withAnyParameters()
      ->willReturn($this->createMock(ConfigEntityType::class));
    $this->container->set('entity_type.manager', $this->entityTypeManager);

    $this->container->set('hs_bugherd', $this->getBugherdService(TRUE));
    $module_handler = $this->createMock(ModuleHandler::class);
    $module_handler->method('getImplementations')
      ->withAnyParameters()
      ->willReturn([]);
    $this->container->set('module_handler', $module_handler);

    $this->container->set('renderer', $this->createMock(Renderer::class));
    $this->container->set('messenger', $this->createMock(Messenger::class));
    $this->container->set('url_generator', $this->createMock(UrlGenerator::class));
    $this->container->set('jira_rest_wrapper_service', $this->getJiraService(TRUE));
    $this->container->set('cache.default', $this->createMock(CacheBackendInterface::class));
    \Drupal::setContainer($this->container);
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
            'project_id' => 999,
          ],
          [
            'id' => 12345,
            'target_url' => 'http://example.com',
            'event' => 'issue_create',
            'project_id' => 123,
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
      999 => 'Test Project',
      123 => 'Second project',
    ]);
    $bugherd_api->setApiKey(Argument::type('string'))->willReturn();
    $bugherd_api->getOrganization()->willReturn([
      'organization' => [
        'id' => 99,
        'name' => 'TEST',
      ],
    ]);
    $bugherd_api->getProject(Argument::type('integer'))
      ->willReturn([
        'project' => ['devurl' => 'http://example.com'],
        'devurl' => 'http://example.com',
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

  /**
   * @param $class
   *
   * @return string
   */
  public function entityTypeRepositoryCallback($class) {
    if ($class == BugherdConnection::class) {
      return 'bugherd_connection';
    }
    if ($class == Key::class) {
      return 'key';
    }
  }

  /**
   * @param array $ids
   *
   * @return array
   */
  public function configEntityStorageCallback($ids = []) {
    return [];
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
        'issue-related-events-section' => "summary ~ 'BUGHERD-*'",
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