<?php

namespace Drupal\Tests\hs_table_filter\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\hs_table_filter\Plugin\Filter\HsTableFilter
 *
 * @group hs_table_filter
 */
class HsTableFilterTest extends UnitTestCase {

  /**
   * @covers ::process
   */
  public function testTableFilter() {
    $this->assertEquals(1, 1);
  }

}
