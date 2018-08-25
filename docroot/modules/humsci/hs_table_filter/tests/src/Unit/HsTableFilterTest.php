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
   * @var HsTableFilter
   */
  protected $filter;

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
  }

  /**
   * @covers ::process
   * @covers ::addDivAttributes
   * @covers ::findCellLabel
   * @covers ::findCellPositionInRow
   * @covers ::findCellFirstSibling
   * @covers ::setAttributesForTable
   * @covers ::setAttributesForCaption
   * @covers ::setAttributesForTbody
   * @covers ::setAttributesForThead
   * @covers ::setAttributesForTh
   * @covers ::setAttributesForTr
   * @covers ::setAttributesForTd
   * @covers ::addClassToNode
   */
  public function testTableFilter() {
    $converted_text = $this->filter->process('
<div>
  <table>
    <caption>Top Caption</caption>
    <thead>
      <tr>
        <th scope="col">1</th>
        <th scope="col">2</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th>3</th>
        <td>4</td>
      </tr>
      <tr>
        <td>5</td>
        <td>6</td>
      </tr>
      <tr>
        <td>7</td>
        <td>8</td>
      </tr>
    </tbody>
  </table>
</div>', 'en');

    foreach ($this->tags as $tag) {
      preg_match_all("/<$tag>/", $converted_text, $output_array);
      $this->assertEmpty(array_filter($output_array));
    }
  }

}
