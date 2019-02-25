<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldFormatterDefault;
use Drupal\views\ViewExecutable;

/**
 * Override the default formatter plugin for view fields and add titles.
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldFormatter
 */
class HsViewfieldFormatterDefault extends ViewfieldFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Add and customize the view title.
    foreach ($this->getFieldValues($items) as $delta => $value) {
      // Either the view is empty or the user doesn't want to display the title.
      if (!$value['show_title'] || !$this->viewHasResults($elements[$delta]['#content']['#view'])) {
        continue;
      }

      $elements[$delta]['#label_display'] = 'above';
      if ($value['override_title'] && $value['overridden_title']) {
        $elements[$delta]['#title'] = $value['overridden_title'];
      }
    }
    return $elements;
  }

  /**
   * Get the values from the entity or from the items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field list items.
   *
   * @return array
   *   Keyed array of field values.
   */
  protected function getFieldValues(FieldItemListInterface $items) {
    if ($this->getFieldSetting('force_default')) {
      $values = $this->fieldDefinition->getDefaultValue($items->getEntity());
    }
    else {
      $values = [];
      foreach ($items as $delta => $item) {
        $values[$delta] = $item->getValue();
      }
    }
    return $values;
  }

  /**
   * Check if there is something to display from the view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   Executed view object.
   *
   * @return bool
   *   If the view has results or an empty result display.
   */
  protected function viewHasResults(ViewExecutable $view) {
    if ($view->result || $view->empty) {
      return TRUE;
    }
  }

}
