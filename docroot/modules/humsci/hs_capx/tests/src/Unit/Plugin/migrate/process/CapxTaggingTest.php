<?php

namespace Drupal\Tests\hs_capx\Unit\Plugin\migrate\process;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\hs_capx\Entity\CapxImporter;
use Drupal\hs_capx\Plugin\migrate\process\CapxTagging;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Class CapxTaggingTest.
 *
 * @group hs_capx
 * @coversDefaultClass \Drupal\hs_capx\Plugin\migrate\process\CapxTagging
 */
class CapxTaggingTest extends UnitTestCase {

  protected $matchingUrls = FALSE;

  protected static $url = 'http://domain.org';

  /**
   * Test the importer tagging process plugin.
   */
  public function testPluginTransformWithoutImporter() {
    $container = new Container();

    $importer = $this->createMock(CapxImporter::class);
    $importer->method('getCapxUrls')
      ->willReturnCallback([$this, 'importerUrlCallback']);
    $importer->method('getFieldTags')
      ->willReturn([1, 2, 3]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([$importer]);

    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $container->set('entity_type.manager', $entity_type_manager);

    $configuration = [];
    $definition = [];
    $plugin = CapxTagging::create($container, $configuration, 'capx_tagging', $definition);

    $this->assertTrue($plugin->multiple());

    $migrate_executable = $this->createMock(MigrateExecutable::class);
    $row = $this->createMock(Row::class);
    $row->method('getSourceProperty')->willReturn(self::$url);
    $destination_property = 'field_terms';

    // Urls dont match.
    $tranformed_value = $plugin->transform(NULL, $migrate_executable, $row, $destination_property);
    $this->assertNull($tranformed_value);

    // Urls do match.
    $tranformed_value = $plugin->transform(NULL, $migrate_executable, $row, $destination_property);
    $this->assertEquals([1, 2, 3], $tranformed_value);
  }

  /**
   * Importer entity callback.
   *
   * @return array
   *   Array of urls.
   */
  public function importerUrlCallback() {
    if ($this->matchingUrls) {
      return [self::$url];
    }
    $this->matchingUrls = !$this->matchingUrls;
    return [$this->getRandomGenerator()->string()];
  }

}
