<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Template\Attribute;
use Drupal\stanford_fields\Plugin\Field\FieldFormatter\EntityTitleHeading as TitleHeading;

/**
 * Provide a string field to be used as a heading.
 *
 * @FieldFormatter(
 *   id = "entity_title_heading",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class EntityTitleHeading extends TitleHeading {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $attributes = new Attribute();
    $classes = $this->getSetting('classes');
    if (!empty($classes)) {
      $attributes->addClass($classes);
    }

    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$element) {
      $element['#atributes'] = $attributes->toArray();
    }
    return $elements;

  }

}
