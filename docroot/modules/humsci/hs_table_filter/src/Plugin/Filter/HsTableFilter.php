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

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    dpm($result);
    $dom = new \DOMDocument();
    $dom->loadHTML($result, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $table_count = $dom->getElementsByTagName('table')->length;
    for ($i = 0; $i < $table_count; $i++) {
      // We always get item(0) because each time we replace a table, the next
      // one becomes item(0).
      if ($table = $dom->getElementsByTagName('table')->item(0)) {
        $table->setAttribute('class', 'div-table');
        $this->changeName($table, 'div');
      }
    }

    //    /** @var \DOMElement $table_element */
    //    foreach ($dom->getElementsByTagName('table') as $table_element) {
    //      dpm($table_element);
    //      $this->clonishNode($table_element, 'div');
    //    }

    dpm($dom->saveHTML());
    return $result;
    return $dom->saveHTML();
  }

  /**
   * @param \DOMNode $oldNode
   * @param $newName
   * @param null $newNS
   */
  protected function changeTags(\DOMNode $oldNode, $newName, $newNS = NULL) {
    if (isset($newNS)) {
      $newNode = $oldNode->ownerDocument->createElementNS($newNS, $newName);
    }
    else {
      $newNode = $oldNode->ownerDocument->createElement($newName);
    }

    foreach ($oldNode->attributes as $attr) {
      $newNode->appendChild($attr->cloneNode());
    }

    foreach ($oldNode->childNodes as $child) {
      $newNode->appendChild($child->cloneNode(TRUE));
    }

    $oldNode->parentNode->replaceChild($newNode, $oldNode);
  }

  function changeName($node, $name) {
    $newnode = $node->ownerDocument->createElement($name);
    foreach ($node->childNodes as $child) {
      $child = $node->ownerDocument->importNode($child, TRUE);
      $newnode->appendChild($child, TRUE);
    }
    foreach ($node->attributes as $attrName => $attrNode) {
      $newnode->setAttribute($attrName, $attrNode);
    }
    $newnode->ownerDocument->replaceChild($newnode, $node);
    return $newnode;
  }

}
