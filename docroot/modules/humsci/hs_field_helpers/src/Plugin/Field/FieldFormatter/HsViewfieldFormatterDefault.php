<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldFormatterDefault;
use Drupal\views\ViewExecutable;

/**
 * Override the default formatter plugin for view fields and add titles.
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldFormatter
 */
class HsViewfieldFormatterDefault extends ViewfieldFormatterDefault {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    try {
      $elements = parent::viewElements($items, $langcode);
    }
    catch (\Throwable $e) {
      if ($e instanceof EnforcedResponseException) {
        throw $e;
      }

      $this->getLogger('hs_field_helpers')
        ->error('Error during rendering: ' . $e->getMessage());
      return [
        '#markup' => $this->t('An error occurred when generating your content.'),
      ];
    }

    // Add and customize the view title.
    foreach ($this->getFieldValues($items) as $delta => $value) {
      if (empty($elements[$delta]['#content']['#view'])) {
        continue;
      }

      $view = $elements[$delta]['#content']['#view'];

      // Either the view is empty or the user doesn't want to display the title.
      if (!$value['show_title'] || !$this->viewHasResults($view)) {
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
