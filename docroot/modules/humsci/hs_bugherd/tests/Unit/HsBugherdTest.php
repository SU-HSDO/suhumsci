<?php

namespace Drupal\Tests\hs_bugherd\Unit;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\Tests\UnitTestCase;

/**
 * Class BugherdResourceBaseTest.
 *
 * @covers \Drupal\hs_bugherd\HsBugherd
 * @group hs_bugherd
 */
class HsBugherdTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\Entity|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProvider|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The entity values.
   *
   * @var array
   */
  protected $values;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('key')
      ->will($this->returnValue('key'));
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('key')
      ->willReturn($this->getMockForAbstractClass(ConfigEntityStorageInterface::class));

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with('Drupal\key\Entity\Key')
      ->willReturn('key');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_type.repository', $entity_type_repository);

    $config = [
      'bugherdapi.settings' => [
        'api_key' => 'stuff',
      ],
    ];
    $container->set('config.factory', $this->getConfigFactoryStub($config));

    \Drupal::setContainer($container);
  }

  /**
   * Test the bugherd resource api functions correctly.
   */
  public function testResourceBase() {
    $random_string = $this->getRandomGenerator()->string();
    $bugherd = new HsBugherd();
    $this->assertEmpty($bugherd->projectKey);
    $bugherd->setProjectId($random_string);
    $this->assertNotEmpty($bugherd->projectKey);

    $bugherd->setApiKey($random_string);
    $this->assertFalse($bugherd->isConnectionSuccessful());

    // Task and project methods.
    $this->assertArrayHasKey('error', $bugherd->getOrganization());
    $this->assertArrayHasKey('error', $bugherd->getTasks());
    $this->assertArrayHasKey('error', $bugherd->updateTask($random_string, []));
    $this->assertArrayHasKey('error', $bugherd->getComments($random_string));

    // User methods.
    $this->assertArrayHasKey('error', $bugherd->getAllUsers());
    $this->assertArrayHasKey('error', $bugherd->getAllUsers(FALSE));
    $this->assertArrayHasKey('error', $bugherd->getAllUsers(TRUE, FALSE));
    $this->assertEmpty($bugherd->getAllUsers(FALSE, FALSE));
    $this->assertArrayHasKey('error', $bugherd->getMembers());
    $this->assertArrayHasKey('error', $bugherd->getGuests());

    // Hook methods.
    $this->assertArrayHasKey('error', $bugherd->getHooks());
    $this->assertArrayHasKey('error', $bugherd->deleteWebhook(123456));
    $this->assertArrayHasKey('error', $bugherd->createWebhook(['event' => $random_string]));
    $this->expectException(\Exception::class);
    $bugherd->createWebhook([]);
  }

  /**
   * Test that the addComment method throws the proper error.
   *
   * @throws \Exception
   */
  public function testAddComment() {
    $random_string = $this->getRandomGenerator()->string();
    $bugherd = new HsBugherd();
    $bugherd->addComment($random_string, ['text' => $random_string]);
    $this->expectException(\Exception::class);
    $bugherd->addComment($random_string, []);
  }

}
