<?php

namespace Drupal\hs_table_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

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

    foreach ($this->tableTags as $tag) {
      /** @var \DOMElement $node */
      foreach ($dom->getElementsByTagName($tag) as $node) {
        $this->setNodeAttributes($node);
      }
    }

    // Get only the context inside the body tag.
    preg_match_all("/<body>(.*?)<\/body>/s", $dom->saveHTML(), $output_array);
    return $output_array[1][0];
  }

  /**
   * Set table tag attributes.
   *
   * @param \DOMElement $node
   *   Table tag to set attributes.
   */
  protected function setNodeAttributes(\DOMElement $node) {
    $tag_properties = $this->getAttributesForTag($node->tagName);
    foreach ($tag_properties as $property => $value) {
      if (is_array($value)) {
        $value = $node->getAttribute($property) . implode(' ', $value);
      }
      $node->setAttribute($property, trim($value));
    }

    if ($node->tagName == 'td' && $label = $this->findCellLabel($node)) {
      $node->setAttribute('aria-label', $label);
    }
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

    $position = $this->findCellPositionInRow($cell);
    /** @var \DOMElement $table */
    $table = $cell->parentNode;
    while ($table->tagName != 'table') {
      $table = $table->parentNode;
    }

    /** @var \DOMNodeList $headings */
    $headings = $xpath->query("thead/tr/th", $table);

    $label = [];
    // Table with top headers.
    if ($headings->item($position)) {
      $label[] = $headings->item($position)->nodeValue;
    }

    $first_row_cell = $this->findCellFirstSibling($cell);
    // Table with headers in the first column.
    if ($first_row_cell && $first_row_cell->tagName == 'th') {
      // When a table has both top and side headers, we want to label the cell
      // with both values.
      $label[] = $first_row_cell->nodeValue;
    }

    return implode(', ', $label);
  }

  /**
   * Find what position the cell is in the row.
   *
   * @param \DOMElement $cell
   *   The table `td` cell.
   *
   * @return int
   *   Position in the row.
   */
  protected function findCellPositionInRow(\DOMElement $cell) {
    $position = 0;
    $sibling = $cell->previousSibling;
    while ($sibling) {
      if (isset($sibling->tagName)) {
        $position++;
      }
      $sibling = $sibling->previousSibling;
    }
    return $position;
  }

  /**
   * Find the first table cell in the same row.
   *
   * @param \DOMElement $cell
   *   A table `td` element.
   *
   * @return \DOMElement|null
   *   The first cell in the row, if any.
   */
  protected function findCellFirstSibling(\DOMElement $cell) {
    $first_cell_in_row = NULL;
    $sibling = $cell->previousSibling;
    while ($sibling) {
      if (isset($sibling->tagName)) {
        /** @var \DOMElement $first_cell_in_row */
        $first_cell_in_row = $sibling;
      }
      $sibling = $sibling->previousSibling;
    }
    return $first_cell_in_row;
  }

  /**
   * Get attributes for a table tag.
   *
   * @param string $tag
   *   Html tag from tables.
   *
   * @return array
   *   Tag attributes.
   */
  protected function getAttributesForTag($tag) {
    $attributes = [];
    switch ($tag) {
      case 'table':
        $attributes['class'][] = 'table-pattern';
        $attributes['aria-readonly'][] = 'true';
        $attributes['role'] = 'grid';
        break;

      case 'caption':
        $attributes['class'][] = 'table-caption';
        break;

      case 'tbody':
        $attributes['class'][] = 'table-body';
        break;

      case 'thead':
        $attributes['class'][] = 'table-header';
        $attributes['role'] = 'row';
        break;

      case 'th':
        $attributes['class'][] = 'table-header-cell';
        $attributes['role'] = 'gridcell';
        break;

      case 'tr':
        $attributes['class'][] = 'table-row';
        $attributes['role'] = 'row';
        break;

      case 'td':
        $attributes['class'][] = 'table-cell';
        $attributes['role'] = 'gridcell';
        break;
    }
    return $attributes;
  }

}
