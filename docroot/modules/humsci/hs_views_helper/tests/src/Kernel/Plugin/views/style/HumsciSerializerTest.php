<?php

namespace Drupal\Tests\hs_views_helper\Kernel\Plugin\views\filter;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;

/**
 * Class HumsciSerializerTest.
 *
 * @group hs_views_helper
 * @coversDefaultClass \Drupal\hs_views_helper\Plugin\views\style\HumsciSerializer
 */
class HumsciSerializerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'views',
    'hs_views_helper',
    'hs_views_helper_test_config',
    'node',
    'user',
    'field',
    'filter',
    'text',
    'datetime',
    'serialization',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig([
      'system',
      'field',
      'node',
      'hs_views_helper_test_config',
    ]);

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    Node::create(['title' => 'Content', 'type' => 'page'])->save();
  }

  /**
   * Test the serializer without custom tags.
   */
  public function testSerializerNoCustom() {
    $view = View::load('test_serializer');
    $this->assertInstanceOf(View::class, $view);
    $render = $view->getExecutable()->render('rest_export_1');

    $this->assertArrayHasKey('#markup', $render);
    $markup = (string) $render['#markup'];

    $dom = new \DOMDocument();
    $dom->loadXML($markup);
    $this->assertEquals(0, $dom->getElementsByTagName('testRoot')->count());
    $this->assertEquals(0, $dom->getElementsByTagName('testItem')->count());
    $this->assertEquals(1, $dom->getElementsByTagName('response')->count());
    $this->assertEquals(1, $dom->getElementsByTagName('item')->count());

    $view = View::load('test_serializer');
    $display_setting = $view->get('display');
    $view->set('display', $display_setting);
  }

  /**
   * Test the serializer with custom tags.
   */
  public function testSerializerCustom() {
    $view = View::load('test_serializer');
    $this->assertInstanceOf(View::class, $view);

    $display_setting = $view->get('display');
    $display_setting['rest_export_1']['display_options']['style']['options']['root_tag'] = 'testRoot';
    $display_setting['rest_export_1']['display_options']['style']['options']['item_tag'] = 'testItem';
    $view->set('display', $display_setting);

    $render = $view->getExecutable()->render('rest_export_1');

    $this->assertArrayHasKey('#markup', $render);
    $markup = (string) $render['#markup'];

    $dom = new \DOMDocument();
    $dom->loadXML($markup);
    $this->assertEquals(1, $dom->getElementsByTagName('testRoot')->count());
    $this->assertEquals(1, $dom->getElementsByTagName('testItem')->count());
    $this->assertEquals(0, $dom->getElementsByTagName('response')->count());
    $this->assertEquals(0, $dom->getElementsByTagName('item')->count());
  }

}
