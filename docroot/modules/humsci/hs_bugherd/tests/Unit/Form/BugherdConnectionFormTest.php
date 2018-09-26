<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\Language;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\Form\BugherdConnectionForm;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\Tests\UnitTestCase;

/**
 * Class BugherdConnectionFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionForm
 * @group hs_bugherd
 */
abstract class BugherdConnectionFormTest extends UnitTestCase {

  /**
   * Test Entity form.
   */
  public function testBugherdConnectionForm() {
    $this->assertEquals(1, 1);
  }

}
