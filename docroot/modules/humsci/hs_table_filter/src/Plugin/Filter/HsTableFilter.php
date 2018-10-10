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

  /**
   * Table tags that should be replaced.
   *
   * @var array
   */
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
    if ($text) {
      $text = $this->addDivAttributes($text);
      foreach ($this->tableTags as $tag) {
        // Replace tags that have other attributes.
        $text = preg_replace("/<$tag (.*?)\/$tag>/s", "<div $1/div>", $text);
        // Replace tags without any attributes.
        $text = preg_replace("/<$tag>(.*?)\/$tag>/s", "<div>$1/div>", $text);
      }
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
    $dom->loadHtml(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));

    foreach ($this->tableTags as $tag) {
      /** @var \DOMElement $node */
      foreach ($dom->getElementsByTagName($tag) as $node) {
        $method = 'setAttributesFor' . ucfirst($node->tagName);
        call_user_func([$this, $method], $node);
      }
    }

    // Get only the context inside the body tag.
    preg_match_all("/<body>(.*?)<\/body>/s", $dom->saveHTML(), $output_array);
    return $output_array[1][0];
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
   * Sets any attributes for table elements.
   *
   * @param \DOMElement $node
   *   Table element.
   */
  protected function setAttributesForTable(\DOMElement $node) {
    static::addClassToNode($node, 'table-pattern');
    $node->setAttribute('role', 'grid');
    $node->setAttribute('aria-readonly', 'true');
  }

  /**
   * Sets any attributes for caption elements.
   *
   * @param \DOMElement $node
   *   Caption element.
   */
  protected function setAttributesForCaption(\DOMElement $node) {
    static::addClassToNode($node, 'table-caption');
  }

  /**
   * Sets any attributes for tbody elements.
   *
   * @param \DOMElement $node
   *   Tbody element.
   */
  protected function setAttributesForTbody(\DOMElement $node) {
    static::addClassToNode($node, 'table-body');
  }

  /**
   * Sets any attributes for thead elements.
   *
   * @param \DOMElement $node
   *   Thead element.
   */
  protected function setAttributesForThead(\DOMElement $node) {
    static::addClassToNode($node, 'table-header');
    $node->setAttribute('role', 'row');
  }

  /**
   * Sets any attributes for th elements.
   *
   * @param \DOMElement $node
   *   Th element.
   */
  protected function setAttributesForTh(\DOMElement $node) {
    static::addClassToNode($node, 'table-header-cell');
    $node->setAttribute('role', 'gridcell');
  }

  /**
   * Sets any attributes for tr elements.
   *
   * @param \DOMElement $node
   *   Tr element.
   */
  protected function setAttributesForTr(\DOMElement $node) {
    static::addClassToNode($node, 'table-row');
    $node->setAttribute('role', 'row');
  }

  /**
   * Sets any attributes for td elements.
   *
   * @param \DOMElement $node
   *   Td element.
   */
  protected function setAttributesForTd(\DOMElement $node) {
    static::addClassToNode($node, 'table-cell');
    $node->setAttribute('role', 'gridcell');

    if ($label = $this->findCellLabel($node)) {
      $node->setAttribute('aria-label', $label);
    }
  }

  /**
   * Add classes to an element and retain original classes.
   *
   * @param \DOMElement $node
   *   Element.
   * @param string|string[] $classes
   *   Classes to add.
   */
  protected static function addClassToNode(\DOMElement $node, $classes) {
    $new_classes = is_array($classes) ? implode(' ', $classes) : $classes;
    $existing_classes = $node->getAttribute('class');
    $node->setAttribute('class', trim("$existing_classes $new_classes"));
  }

}
