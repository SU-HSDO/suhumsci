<?php

namespace Drupal\Tests\hs_migrate\Unit\Plulgin\migrate\process;

use Drupal\hs_migrate\Plugin\migrate\process\SubProcess as HsSubProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\process\SubProcess as OrigSubProcess;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Class SubProcessTest.
 *
 * @group hs_migrate
 * @coversDefaultClass \Drupal\hs_migrate\Plugin\migrate\process\SubProcess
 */
class SubProcessTest extends UnitTestCase {

  public function testTranform() {
    $configuration = [
      'key' => 'newkey',
      'process' => [],
    ];
    $definition = [];
    $hs_plugin = new HsSubProcess($configuration, 'sub_process', $definition);
    $original_plugin = new OrigSubProcess($configuration, 'sub_process', $definition);

    $migrate_executable = $this->createMock(MigrateExecutable::class);
    $row = $this->createMock(Row::class);

    $value = simplexml_load_string('<data><item1><newkey>akey</newkey></item1></data>');
    $new_value = $hs_plugin->transform($value, $migrate_executable, $row, 'field_foo');
    $this->assertEquals(['' => []], $new_value);
    $this->expectException(MigrateException::class);
    $original_plugin->transform($value, $migrate_executable, $row, 'field_foo');
  }

}
