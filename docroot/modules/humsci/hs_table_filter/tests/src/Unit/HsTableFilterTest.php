<?php

namespace Drupal\Tests\hs_table_filter\Unit;

use Drupal\hs_table_filter\Plugin\Filter\HsTableFilter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\hs_table_filter\Plugin\Filter\HsTableFilter
 *
 * @group hs_table_filter
 */
class HsTableFilterTest extends UnitTestCase {

  /**
   * Table Filter class.
   *
   * @var \Drupal\hs_table_filter\Plugin\Filter\HsTableFilter
   */
  protected $filter;

  /**
   * Original test html.
   *
   * @var string
   */
  protected $testHtml;

  /**
   * Dom to use during tests.
   *
   * @var \DOMDocument
   */
  protected $dom;

  /**
   * Xpath on dom for use in tests.
   *
   * @var \DOMXPath
   */
  protected $xpath;

  /**
   * Normal html tags in tables.
   *
   * @var array
   */
  protected $tags = [
    'td',
    'tr',
    'th',
    'thead',
    'tbody',
    'caption',
    'table',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $configuration = [
      'description' => '',
      'weight' => 0,
      'status' => TRUE,
      'id' => 'hs_table_filter',
      'title' => 'Table Filter',
      'type' => 2,
      'provider' => 'hs_table_filter',
    ];
    $this->filter = new HsTableFilter($configuration, 'hs_table_filter', $configuration);
    $this->testHtml = file_get_contents(__DIR__ . '/test_table.html');
    $this->dom = new \DOMDocument();
  }

  /**
   * @covers ::process
   */
  public function testTableFilter() {
    $converted_text = (string) $this->filter->process($this->testHtml, 'en');

    foreach ($this->tags as $tag) {
      preg_match_all("/<$tag>/", $converted_text, $output_array);
      $this->assertEmpty(array_filter($output_array));
    }
  }

  /**
   * Set the dom document and xpaths with converted text.
   */
  protected function setTestDom() {
    $converted_text = (string) $this->filter->process($this->testHtml, 'en');
    $this->dom->loadHTML($converted_text);
    $this->xpath = new \DOMXPath($this->dom);
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForTable
   * @covers ::addClassToNode
   */
  public function testTableAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-pattern"]');
    $this->assertEquals(2, $node_list->length);
    foreach ($node_list as $node) {
      $this->assertEquals('grid', $node->getAttribute('role'));
      $this->assertEquals('true', $node->getAttribute('aria-readonly'));
    }
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForCaption
   * @covers ::addClassToNode
   */
  public function testCaptionAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-caption"]');
    $this->assertEquals(2, $node_list->length);
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForTbody
   * @covers ::addClassToNode
   */
  public function testTbodyAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-body"]');
    $this->assertEquals(2, $node_list->length);
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForThead
   * @covers ::addClassToNode
   */
  public function testTheadAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-header"]');
    $this->assertEquals(1, $node_list->length);
    foreach ($node_list as $node) {
      $this->assertEquals('row', $node->getAttribute('role'));
    }
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForTh
   * @covers ::addClassToNode
   */
  public function testThAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-header-cell"]');
    $this->assertEquals(5, $node_list->length);
    foreach ($node_list as $node) {
      $this->assertEquals('gridcell', $node->getAttribute('role'));
    }
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForTr
   * @covers ::addClassToNode
   */
  public function testTrAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-row"]');
    $this->assertEquals(7, $node_list->length);
    foreach ($node_list as $node) {
      $this->assertEquals('row', $node->getAttribute('role'));
    }
  }

  /**
   * @covers ::addDivAttributes
   * @covers ::setAttributesForTd
   * @covers ::addClassToNode
   * @covers ::findCellLabel
   * @covers ::findCellPositionInRow
   * @covers ::findCellFirstSibling
   */
  public function testTdAttributes() {
    $this->setTestDom();

    $node_list = $this->xpath->query('//div[@class="table-cell"]');
    $this->assertEquals(9, $node_list->length);
    foreach ($node_list as $node) {
      $this->assertEquals('gridcell', $node->getAttribute('role'));
      $this->assertNotEmpty($node->getAttribute('aria-label'));
    }
  }

  /**
   * @covers ::process
   */
  public function testEmptyText() {
    $this->assertEquals('', $this->filter->process('', '')->getProcessedText());
  }

}
