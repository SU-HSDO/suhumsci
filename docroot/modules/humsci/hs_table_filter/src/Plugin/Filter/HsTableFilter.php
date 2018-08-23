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
    dpm($text);
    return new FilterProcessResult($text);
  }

  protected function addDivAttributes($text) {
    $dom = new \DOMDocument();
    $dom->loadHtml($text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    /** @var \DOMElement $table */
    foreach ($dom->getElementsByTagName('table') as $table) {
      $table->setAttribute('role', 'grid');
      $table->setAttribute('aria-readonly', 'true');
      $classes = $table->getAttribute('class') ?: '';
      $table->setAttribute('class', trim("$classes 'table-pattern"));
    }

    /** @var \DOMElement $table_head */
    foreach ($dom->getElementsByTagName('thead') as $table_head) {
      $table_head->setAttribute('role', 'row');
    }
    /** @var \DOMElement $row */
    foreach ($dom->getElementsByTagName('tr') as $row) {
      $row->setAttribute('class', 'table-row');
      $row->setAttribute('role', 'row');
    }
    /** @var \DOMElement $cell */
    foreach ($dom->getElementsByTagName('td') as $cell) {
      $cell->setAttribute('role', 'gridcell');
    }

    return $dom->saveHTML();
  }

}
