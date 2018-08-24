<?php

namespace Drupal\hs_table_filter\Plugin\Filter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\linkit\SubstitutionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Linkit filter.
 *
 * @Filter(
 *   id = "hs_table_filter",
 *   title = @Translation("Table converter"),
 *   description = @Translation("Converts table tags into structured divs."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class HsTableFilter extends FilterBase {

  protected $tableTags = [
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
  public function process($text, $langcode) {
    $text = $this->addDivAttributes($text);
    foreach ($this->tableTags as $tag) {
      // Replace tags that have other attributes.
      $text = preg_replace("/<$tag (.*?)\/$tag>/s", "<div $1/div>", $text);
      // Replace tags without any attributes.
      $text = preg_replace("/<$tag>(.*?)\/$tag>/s", "<div>$1/div>", $text);
    }
    return new FilterProcessResult($text);
  }

  /**
   * Add appropriate attributes such as classes and roles to table tags.
   *
   * @param string $text
   *   Text with tables.
   *
   * @return string
   *   Modified text.
   */
  protected function addDivAttributes($text) {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadHtml($text);

    /** @var \DOMElement $table */
    foreach ($dom->getElementsByTagName('table') as $table) {
      $table->setAttribute('role', 'grid');
      $table->setAttribute('aria-readonly', 'true');
      $classes = $table->getAttribute('class') ?: '';
      $table->setAttribute('class', trim("$classes table-pattern"));
    }

    foreach ($dom->getElementsByTagName('caption') as $caption) {
      $caption->setAttribute('class', 'table-caption');
    }

    foreach ($dom->getElementsByTagName('tbody') as $tbody) {
      $tbody->setAttribute('class', 'table-body');
    }

    /** @var \DOMElement $table_head */
    foreach ($dom->getElementsByTagName('thead') as $table_head) {
      $table_head->setAttribute('class', 'table-header');
      $table_head->setAttribute('role', 'row');
    }

    /** @var \DOMElement $cell */
    foreach ($dom->getElementsByTagName('th') as $cell) {
      $cell->setAttribute('class', 'table-header-cell');
      $cell->setAttribute('role', 'gridcell');
    }

    /** @var \DOMElement $row */
    foreach ($dom->getElementsByTagName('tr') as $row) {
      $row->setAttribute('class', 'table-row');
      $row->setAttribute('role', 'row');
    }

    /** @var \DOMElement $cell */
    foreach ($dom->getElementsByTagName('td') as $cell) {
      $cell->setAttribute('class', 'table-cell');
      $cell->setAttribute('role', 'gridcell');

      if ($label = $this->findCellLabel($cell)) {
        $cell->setAttribute('data-column-label', $label);
        $cell->setAttribute('aria-label', $label);
      }
    }

    preg_match_all("/<body>(.*?)<\/body>/s", $dom->saveHTML(), $output_array);
    return $dom->saveHTML();
  }

  /**
   * Find a label from a th tag relative to the give table cell.
   *
   * @param \DOMElement $cell
   *   A table cell td.
   *
   * @return string
   *   Table heading values.
   */
  protected function findCellLabel(\DOMElement $cell) {
    $xpath = new \DOMXPath($cell->ownerDocument);

    $i = 0;
    $first_cell_in_row = NULL;
    $sibling = $cell->previousSibling;
    while ($sibling) {
      if (isset($sibling->tagName)) {
        /** @var \DOMElement $first_cell_in_row */
        $first_cell_in_row = $sibling;
        $i++;
      }
      $sibling = $sibling->previousSibling;
    }

    /** @var \DOMElement $table */
    $table = $cell->parentNode;
    while ($table->tagName != 'table') {
      $table = $table->parentNode;
    }

    /** @var \DOMNodeList $headings */
    $headings = $xpath->query("thead/tr/th", $table);

    $label = [];
    // Table with top headers.
    if ($headings->item($i)) {
      $label[] = $headings->item($i)->nodeValue;
    }

    // Table with headers in the first column.
    if ($first_cell_in_row && $first_cell_in_row->tagName == 'th') {
      // When a table has both top and side headers, we want to label the cell
      // with both values.
      $label[] = $first_cell_in_row->nodeValue;
    }

    return implode(', ', $label);
  }

}
