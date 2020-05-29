<?php

/**
 * @file
 * Provides an additional config form for theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

// Set theme name to use in the key values.
$theme_name = \Drupal::theme()->getActiveTheme()->getName();

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * Form override for theme settings.
 */
function humsci_airy_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  // Airy theme color pairing setting
  // theme_color_pairing
  $form['options_settings']['humsci_airy_color_pairing'] = [
    '#type' => 'fieldset',
    '#title' => t('Color Pairing'),
  ];

  $form['options_settings']['humsci_airy_color_pairing']['theme_color_pairing'] = [
    '#type' => 'select',
    '#title' => t('Color Pairing'),
    '#options' => [
      'blue' => t('Blue'),
      'green' => t('Green'),
    ],
    '#default_value' => theme_get_setting('theme_color_pairing'),
  ];
}
