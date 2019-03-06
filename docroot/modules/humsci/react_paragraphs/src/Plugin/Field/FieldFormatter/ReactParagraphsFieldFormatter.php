<?php

namespace Drupal\react_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Plugin implementation of the 'react_paragraphs' formatter.
 *
 * @FieldFormatter(
 *   id = "react_paragraphs",
 *   label = @Translation("React Paragraphs"),
 *   field_types = {
 *     "react_paragraphs"
 *   }
 * )
 */
class ReactParagraphsFieldFormatter extends EntityReferenceRevisionsEntityFormatter {

  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    if (!isset($elements['#items'])) {
      return $elements;
    }

    foreach ($elements['#items'] as $item) {
      $item->_attributes = ['class' => ['react-paragraphs-wrapper']];
    }
    $elements['#attached']['library'][] = 'react_paragraphs/field_formatter';
    return $elements;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $row_elements = [];
    $row_item_widths = [];
    for ($delta = 0; $delta < $items->count(); $delta++) {
      $item_settings = $items->get($delta)->getValue()['settings'];
      $item_settings = json_decode($item_settings, TRUE);

      $row_item_widths[$item_settings['row']] = isset($row_item_widths[$item_settings['row']]) ? $row_item_widths[$item_settings['row']] + $item_settings['width'] : $item_settings['width'];

      $item_classes = "react-width-{$item_settings['width']}-of-12";

      $row_elements[$item_settings['row']][] = [
        '#type' => 'container',
        '#attributes' => ['class' => $item_classes],
        $elements[$delta],
      ];
    }

    // If a row doesnt have enough items to fill it, add a spacer at the end to
    // keep that empty area in the row.
    foreach ($row_item_widths as $row_index => $widths) {
      if ($widths < 12) {
        $spacer_width = 12 - $widths;
        $row_elements[$row_index][] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'react-spacer',
              "react-width-$spacer_width-of-12",
            ],
          ],
        ];
      }
    }

    return $row_elements;
  }

}
