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
    $dom = new \DOMDocument();
    $dom->loadHTML($result);

    $oldNode = $dom->getElementsByTagName('table')->item(0);
    $this->clonishNode($oldNode, 'div');

    // Same but with a new namespace
    //clonishNode($oldNode, 'newns:BXR', 'http://newns');

    dpm($dom->saveXML());

    /** @var \DOMElement $table_element */
    foreach ($dom->getElementsByTagName('table') as $table_element) {

    }
    dpm($result);
    return $result;
  }

  /**
   * @param \DOMNode $oldNode
   * @param $newName
   * @param null $newNS
   */
  function clonishNode(\DOMNode $oldNode, $newName, $newNS = NULL) {
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

}
