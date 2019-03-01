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

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $width_classes = [
      1 => 'decanter-width-one-twelfth',
      2 => 'decanter-width-one-sixth',
      3 => 'decanter-width-one-fourth',
      4 => 'decanter-width-one-third',
      5 => 'decanter-width-five-twelfths',
      6 => 'decanter-width-one-half',
      7 => 'decanter-width-seven-twelfths',
      8 => 'decanter-width-three-fourths',
      9 => 'decanter-width-two-thirds',
      10 => 'decanter-width-five-sixths',
      11 => 'decanter-width-eleven-twelfths',
    ];
    $row_data = [];
    for ($delta = 0; $delta < $items->count(); $delta++) {
      $item_settings = $items->get($delta)->getValue()['settings'];
      $item_settings = json_decode($item_settings, TRUE);

      $item_classes = isset($width_classes[$item_settings['width']]) ? [$width_classes[$item_settings['width']]] : [];

      $row_data[$item_settings['row']]['#type'] = 'container';
      $row_data[$item_settings['row']]['#attributes'] = [
        'class' => [
          'item-row',
          'clearfix',
        ],
      ];
      $row_data[$item_settings['row']][] = [
        '#type' => 'container',
        '#attributes' => ['class' => $item_classes],
        'item' => $elements[$delta],
      ];
    }

    return $row_data;
  }

}
