<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_date\Plugin\Field\FieldFormatter\SmartDateCustomFormatter;

/**
 * Plugin implementation of the 'Custom' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "smartdatetime_hs",
 *   label = @Translation("Custom Single"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateTime extends SmartDateCustomFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = ['display' => 'start'];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode, $format = '') {
    $elements = parent::viewElements($items, $langcode, $format);
    $display = $this->getSetting('display');
    foreach ($elements as &$element) {
      if ($display == 'start') {
        $element = $element[$display];
        continue;
      }
      $element = [
        'date' => $element['end']['date'] ?? $element['start']['date'],
        'join' => $element['end']['join'] ?? $element['start']['join'],
        'time' => $element['end']['time'] ?? $element['start']['time'],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['separator'], $form['timezone'], $form['join']);
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#default_value' => $this->getSetting('display'),
      '#weight' => -10,
      '#options' => [
        'start' => $this->t('Start date only'),
        'end' => $this->t('End date only'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($display = $this->getSetting('display')) {
      $summary[] = $this->t('Display: %display', ['%display' => $display]);
    }
    return $summary;
  }

}
