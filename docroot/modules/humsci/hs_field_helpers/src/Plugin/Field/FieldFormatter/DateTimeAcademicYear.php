<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;

/**
 * Plugin implementation of the 'Custom' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "datetime_academic_year",
 *   label = @Translation("Academic Year"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeAcademicYear extends DateTimeCustomFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['date_format' => 'Y'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only work on "Date Only" fields, not date time fields.
    $type = $field_definition->getFieldStorageDefinition()
      ->getSetting('datetime_type');
    if ($type != 'date') {
      return FALSE;
    }
    return parent::isApplicable($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    // Academic year 2018 is displayed as 2017 - 2018.
    foreach ($elements as &$element) {
      $element['#markup'] = $element['#markup'] - 1 . ' - ' . $element['#markup'];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['date_format']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

}
