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
function humsci_traditional_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  // Traditional theme color pairing setting
  // theme_color_pairing:
  $form['options_settings']['humsci_traditional_color_pairing'] = [
    '#type' => 'fieldset',
    '#title' => t('Color Pairing'),
  ];

  $form['options_settings']['humsci_traditional_color_pairing']['theme_color_pairing'] = [
    '#type' => 'select',
    '#title' => t('Color Pairing'),
    '#options' => [
      'cardinal' => t('Cardinal'),
    ],
    '#default_value' => theme_get_setting('theme_color_pairing'),
  ];

  // Local Footer:
  $form['options_settings']['humsci_traditional_local_footer'] = [
    '#type' => 'fieldset',
    '#title' => t('Local Footer Settings'),
  ];

  $form['options_settings']['humsci_traditional_local_footer']['local_footer_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Local Footer Variant'),
    '#options' => [
      'default' => '- Default -',
      'dark' => t('Dark'),
    ],
    '#default_value' => theme_get_setting('local_footer_variant_classname'),
  ];
}
