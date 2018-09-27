<?php

namespace Drupal\Tests\hs_bugherd\Entity;

use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\Tests\UnitTestCase;

/**
 * Class BugherdConnectionTest.
 *
 * @covers \Drupal\hs_bugherd\Entity\BugherdConnection
 * @group hs_bugherd
 */
class BugherdConnectionTest extends UnitTestCase {

  /**
   * Test Connection entity methods.
   */
  public function testEntity() {
    $connection = new BugherdConnection([
      'bugherdProject' => '999',
      'jiraProject' => 'TEST',
      'statusMap' => [
        'doing' => 123,
      ],
    ], 'bugherd_connection');
    $this->assertEquals('999', $connection->getBugherdProject());
    $this->assertEquals('TEST', $connection->getJiraProject());
    $this->assertEquals('doing', $connection->getBugherdStatus(123));
    $this->assertArrayHasKey('doing', $connection->getStatusMap());

    $connection = new BugherdConnection([], 'bugherd_connection');
    $this->assertNull($connection->getBugherdStatus(123));
  }

}
