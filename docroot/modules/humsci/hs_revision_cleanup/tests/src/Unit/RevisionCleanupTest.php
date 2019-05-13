<?php

namespace Drupal\Tests\hs_revision_cleanup\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\hs_revision_cleanup\RevisionCleanup;
use Drupal\Tests\UnitTestCase;

/**
 * Class RevisionCleanupTest
 *
 * @coversDefaultClass \Drupal\hs_revision_cleanup\RevisionCleanup
 * @group hs_revision_cleanup
 */
class RevisionCleanupTest extends UnitTestCase {

  /**
   * Test the initial error if an entity definition fails.
   */
  public function testCompleteError() {
    $database = $this->createMock(Connection::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->willThrowException(new \Exception('Failure'));

    $logger = $this->createMock(LoggerChannelInterface::class);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);

    $config = $this->createMock(Config::class);
    $config->method('get')->willReturn([
      [
        'entity_type' => 'node',
        'keep' => 1,
      ],
    ]);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturn($config);

    $cleanup = new RevisionCleanup($database, $entity_type_manager, $logger_factory, $config_factory);
    $this->assertNull($cleanup->deleteRevisions());
  }

  /**
   * Test if a single entity fails to load error.
   */
  public function testIndividualError() {
    $query = $this->createMock(StatementInterface::class);
    $query->method('fetchAssoc')->willReturnCallback([
      $this,
      'fetchAssocCallback',
    ]);

    $select = $this->createMock(Select::class);
    $select->method('fields')->will($this->returnValue($select));
    $select->method('execute')->willReturn($query);

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->method('getKey')
      ->willReturnCallback(function ($key) {
        return $key == 'id' ? 'nid' : 'vid';
      });
    $entity_type_definition->method('isRevisionable')->willReturn(TRUE);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->willReturn($entity_type_definition);
    $entity_type_manager->method('getStorage')->willThrowException(new \Exception('Failed'));

    $logger = $this->createMock(LoggerChannelInterface::class);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);

    $config = $this->createMock(Config::class);
    $config->method('get')->willReturn([
      [
        'entity_type' => 'node',
        'keep' => 2,
      ],
    ]);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturn($config);

    $cleanup = new RevisionCleanup($database, $entity_type_manager, $logger_factory, $config_factory);
    $this->assertNull($cleanup->deleteRevisions());
  }

  /**
   * Db fecth callback.
   *
   * @return array
   *   Array of data.
   */
  public function fetchAssocCallback() {
    static $count = 1;
    $count++;

    if ($count < 10) {
      return ['nid' => 1, 'vid' => $count];
    }
  }

}
