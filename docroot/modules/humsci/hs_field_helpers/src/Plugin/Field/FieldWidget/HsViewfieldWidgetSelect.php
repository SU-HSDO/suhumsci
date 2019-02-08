<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\viewfield\Plugin\Field\FieldWidget\ViewfieldWidgetSelect;

/**
 * Class ViewfieldWithTitle.
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldWidget
 */
class HsViewfieldWidgetSelect extends ViewfieldWidgetSelect {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items->get($delta);
    $item_values = $item->getValue();
    $item_values += [
      'show_title' => 0,
      'override_title' => 0,
      'overridden_title' => '',
    ];

    // Strip the view options as defined by the exclude views settings.
    // @see hs_field_helpers_form_field_config_edit_form_alter().
    if ($excluded_views = $this->fieldDefinition->getThirdPartySetting('hs_field_helpers', 'excluded_views')) {
      $element['target_id']['#options'] = array_diff_key($element['target_id']['#options'], array_flip($excluded_views));
    }
    // Chosen & Material Admin conflicts with this select list during ajax.
    // The easiest way to fix is to disable those libraries.
    $element['display_id']['#attributes']['class'][] = 'browser-default';

    if ($items->getFieldDefinition()->getSetting('allow_title_customizing')) {
      $element['show_title'] = [
        '#type' => 'checkbox',
        '#title' => t('Show view title'),
        '#weight' => -10,
        '#default_value' => $item_values['show_title'],
      ];
      $element['override_title'] = [
        '#type' => 'checkbox',
        '#title' => t('Override view title'),
        '#weight' => -9,
        '#default_value' => $item_values['override_title'],
      ];
      $element['overridden_title'] = [
        '#type' => 'textfield',
        '#title' => t('Custom title'),
        '#weight' => -8,
        '#default_value' => $item_values['overridden_title'],
      ];
    }
    return $element;
  }

}
