<?php

namespace Drupal\Tests\hs_migrate\Unit\Plulgin\migrate\process;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\hs_migrate\Plugin\migrate\process\HsEntityGenerate;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Class HsEntityGenerateTest.
 *
 * @group hs_migration
 * @coversDefaultClass \Drupal\hs_migrate\Plugin\migrate\process\HsEntityGenerate
 */
class HsEntityGenerateTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new Container();

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn(123);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('create')
      ->willReturn($entity);
    $entity_storage->method('load')
      ->willReturn($entity);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->will($this->returnValue($query));

    $query->method('accessCheck')->will($this->returnValue($query));

    $entity_storage->method('getQuery')
      ->willReturn($query);

    $entity_manager = $this->createMock(EntityManager::class);
    $entity_manager->method('getStorage')
      ->willReturn($entity_storage);

    $this->container->set('entity.manager', $entity_manager);
    $this->container->set('plugin.manager.entity_reference_selection', $this->createMock(SelectionPluginManagerInterface::class));
    $this->container->set('plugin.manager.migrate.process', $this->createMock(MigratePluginManager::class));
  }

  /**
   * Test tranform method.
   */
  public function testTranform() {

    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('getDestinationPlugin')
      ->willReturn($this->createMock(MigrateDestinationInterface::class));
    $configuration = [
      'entity_type' => 'type',
      'value_key' => 'key',
    ];
    $definition = [];
    $plugin = HsEntityGenerate::create($this->container, $configuration, 'entity_generate_no_lookup', $definition, $migration);

    $migrate_executable = $this->createMock(MigrateExecutable::class);
    $row = $this->createMock(Row::class);

    $this->assertInstanceOf(HsEntityGenerate::class, $plugin);
    $new_value = $plugin->transform('stuff', $migrate_executable, $row, 'field_foo');
  }

}
