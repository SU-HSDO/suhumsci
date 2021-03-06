<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\viewfield\Plugin\Field\FieldWidget\ViewfieldWidgetSelect;

/**
 * Override the default widget for view fields.
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldWidget
 */
class HsViewfieldWidgetSelect extends ViewfieldWidgetSelect {

  /**
   * {@inheritdoc}
   *
   * Adjust the view select widget to expose an option to the user to show and
   * customize the view title.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item_values = $this->getItemValues($items, $delta);

    // Strip the view options as defined by the exclude views settings.
    // @see hs_field_helpers_form_field_config_edit_form_alter().
    if ($excluded_views = $this->fieldDefinition->getThirdPartySetting('hs_field_helpers', 'excluded_views')) {
      $element['target_id']['#options'] = array_diff_key($element['target_id']['#options'], array_flip($excluded_views));
    }
    // Chosen & Material Admin conflicts with this select list during ajax.
    // The easiest way to fix is to disable those libraries.
    $element['display_id']['#attributes']['class'][] = 'browser-default';

    $form_state_keys = [$this->fieldDefinition->getName(), $delta];
    if (!empty($form['#parents'])) {
      $form_state_keys = array_merge($form['#parents'], $form_state_keys);
    }
    $primary_field_name = $form_state_keys[0] . '[' . implode('][', array_slice($form_state_keys, 1)) . '][show_title]';
    $second_field_name = $form_state_keys[0] . '[' . implode('][', array_slice($form_state_keys, 1)) . '][override_title]';
    $primary_visible_test = [':input[name="' . $primary_field_name . '"]' => ['checked' => TRUE]];
    $second_visible_test = [':input[name="' . $second_field_name . '"]' => ['checked' => TRUE]];

    if ($items->getFieldDefinition()->getSetting('allow_title_customizing')) {
      $element['show_title'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show view title'),
        '#weight' => -10,
        '#default_value' => $item_values['show_title'],
      ];
      $element['override_title'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override view title'),
        '#weight' => -9,
        '#default_value' => $item_values['override_title'],
        '#states' => ['visible' => $primary_visible_test],
      ];
      $element['overridden_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom title'),
        '#weight' => -8,
        '#default_value' => $item_values['overridden_title'],
        '#states' => ['visible' => $second_visible_test],
      ];
    }
    return $element;
  }

  /**
   * Get the item array for the field values combined with default values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field item list.
   * @param int $delta
   *   Delta of the list.
   *
   * @return array
   *   Delta's value.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getItemValues(FieldItemListInterface $items, $delta) {
    $item = $items->get($delta);
    $item_values = $item->getValue();
    $item_values += [
      'show_title' => 0,
      'override_title' => 0,
      'overridden_title' => '',
    ];
    return $item_values;
  }

}
