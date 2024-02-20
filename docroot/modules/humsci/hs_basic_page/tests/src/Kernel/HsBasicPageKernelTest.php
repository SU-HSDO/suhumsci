<?php

namespace Drupal\Tests\hs_basic_page\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class HsBasicPageKernelTest.
 *
 * @group hs_basic_page
 */
class HsBasicPageKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'field',
    'menu_ui',
    'layout_builder',
    'paragraphs',
    'entity_reference_revisions',
    'hs_basic_page',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['hs_basic_page']);
  }

  /**
   * Test Basic Page entity gets created.
   */
  public function testBasicPage() {
    $node_type = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load('hs_basic_page');
    $this->assertNotEmpty($node_type);
  }

}
