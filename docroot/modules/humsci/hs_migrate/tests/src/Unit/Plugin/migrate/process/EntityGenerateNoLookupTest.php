<?php

namespace Drupal\Tests\hs_migrate\Unit\Plulgin\migrate\process;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hs_migrate\Plugin\migrate\process\EntityGenerateNoLookup;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Class EntityGenerateNoLookupTest.
 *
 * @group hs_migrate
 * @coversDefaultClass \Drupal\hs_migrate\Plugin\migrate\process\EntityGenerateNoLookup
 */
class EntityGenerateNoLookupTest extends UnitTestCase {

  /**
   * Test the tranform returns an entity.
   */
  public function testTranform() {
    $container = new Container();

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn(123);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('create')
      ->willReturn($entity);

    $entity_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_manager->method('getStorage')
      ->willReturn($entity_storage);

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);

    $container->set('entity_type.manager', $entity_manager);
    $container->set('plugin.manager.entity_reference_selection', $this->createMock(SelectionPluginManagerInterface::class));
    $container->set('plugin.manager.migrate.process', $this->createMock(MigratePluginManager::class));
    $container->set('entity_field.manager', $field_manager);

    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('getDestinationPlugin')
      ->willReturn($this->createMock(MigrateDestinationInterface::class));
    $configuration = [
      'entity_type' => 'type',
      'value_key' => 'key',
    ];
    $definition = [];


    $plugin = EntityGenerateNoLookup::create($container, $configuration, 'entity_generate_no_lookup', $definition, $migration);

    $this->assertInstanceOf(EntityGenerateNoLookup::class, $plugin);

    $migrate_executable = $this->createMock(MigrateExecutable::class);
    $row = $this->createMock(Row::class);
    $entity_id = $plugin->transform('string', $migrate_executable, $row, 'field_stuff');
    $this->assertEquals(123, $entity_id);
  }

}
