<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldFormatterDefault;

/**
 * Class HsViewfieldFormatterDefault
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldFormatter
 */
class HsViewfieldFormatterDefault extends ViewfieldFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if ($this->getFieldSetting('force_default')) {
      $values = $this->fieldDefinition->getDefaultValue($items->getEntity());
    }
    else {
      $values = [];
      foreach ($items as $delta => $item) {
        $values[$delta] = $item->getValue();
      }
    }

    foreach ($values as $delta => $value) {
      if (!$value['show_title']) {
        continue;
      }

      $elements[$delta]['#label_display'] = 'above';
      if ($value['override_title'] && $value['overridden_title']) {
        $elements[$delta]['#title'] = $value['overridden_title'];
      }
    }

    return $elements;
  }

}
