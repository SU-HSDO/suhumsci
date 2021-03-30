<?php

namespace Drupal\Tests\hs_capx\Unit;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\hs_capx\CapxImporterListBuilder;
use Drupal\hs_capx\Entity\CapxImporter;
use Drupal\Tests\UnitTestCase;

/**
 * Class CapxTest.
 *
 * @covers \Drupal\hs_capx\CapxImporterListBuilder
 * @group hs_capx
 */
class CapxImporterListBuilderTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $container = new Container();
    $container->set('string_translation', $this->createMock(TranslationManager::class));

    $module_handler = $this->createMock(ModuleHandler::class);
    $module_handler->method('invokeAll')->willReturn([]);
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);
  }

  public function testListBuilder() {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);

    $list_builder = new CapxImporterListBuilder($entity_type, $storage);
    $header = $list_builder->buildHeader();
    $this->assertCount(4, $header);

    $entity = $this->createMock(CapxImporter::class);
    $entity->method('label')->willReturn('Test Entity');
    $entity->method('getOrganizations')->willReturn('BSWS');
    $entity->method('getWorkgroups')->willReturn('itservices:webservices');

    $row = $list_builder->buildRow($entity);
    $this->assertCount(4, $row);
    $this->assertEquals('Test Entity', $row['label']);
    $this->assertEquals('BSWS', $row['organization']);
    $this->assertEquals('itservices:webservices', $row['workgroups']);
  }
}
