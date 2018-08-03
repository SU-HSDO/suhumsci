<?php

namespace Drupal\mrc_yearonly\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
 *
 * @FieldFormatter (
 *   id = "yearonly_academic",
 *   label = @Translation("Academic Year"),
 *   field_types = {
 *     "yearonly"
 *   }
 * )
 */
class AcademicYearOnly extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['order' => NULL] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    switch ($this->getSetting('order')) {
      case 'desc':
        $summary[] = $this->t('Descending');
        break;
      case 'asc':
        $summary[] = $this->t('Ascending');
        break;
      default:
        $summary[] = $this->t('Sorted on the field edit');
        break;
    }


    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['order'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Order'),
      '#empty_option' => $this->t('Natural'),
      '#default_value' => $this->getSetting('order'),
      '#options' => [
        'asc' => $this->t('Ascending'),
        'desc' => $this->t('Descending'),
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $values = [];
    foreach ($items as $delta => $item) {
      $values[$delta] = $item->value;
    }

    switch ($this->getSetting('order')) {
      case 'desc':
        arsort($values);
        break;
      case 'asc':
        asort($values);
        break;
    }


    foreach ($values as $delta => $value) {
      $element[$delta] = [
        '#theme' => 'yearonly_academic',
        '#start_year' => $value - 1,
        '#end_year' => $value,
      ];
    }

    return $element;
  }

}
