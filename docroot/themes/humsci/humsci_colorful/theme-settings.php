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
function humsci_colorful_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  // Colorful theme color pairing setting
  // theme_color_pairing
  $form['options_settings']['humsci_colorful_color_pairing'] = [
    '#type' => 'fieldset',
    '#title' => t('Color Pairing'),
  ];

  $form['options_settings']['humsci_colorful_color_pairing']['theme_color_pairing'] = [
    '#type' => 'select',
    '#title' => t('Color Pairing'),
    '#options' => [
      'ocean' => t('Ocean'),
      'mountain' => t('Mountain'),
      'cardinal' => t('Cardinal'),
      'lake' => t('Lake'),
      'canyon' => t('Canyon'),
      'cliff' => t('Cliff'),
    ],
    '#default_value' => theme_get_setting('theme_color_pairing'),
  ];

  // Local Masthead
  $form['options_settings']['humsci_colorful_local_masthead'] = [
    '#type' => 'fieldset',
    '#title' => t('Local Masthead Settings'),
  ];

  $form['options_settings']['humsci_colorful_local_masthead']['local_masthead_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Local Masthead Variant'),
    '#options' => [
      'default' => t('- Default -'),
      'dark' => t('Dark'),
    ],
    '#default_value' => theme_get_setting('local_masthead_variant_classname'),
  ];

  // Local Footer
  $form['options_settings']['humsci_colorful_local_footer'] = [
    '#type' => 'fieldset',
    '#title' => t('Local Footer Settings'),
  ];

  $form['options_settings']['humsci_colorful_local_footer']['local_footer_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Local Footer Variant'),
    '#options' => [
      'default' => t('- Default -'),
      'dark' => t('Dark'),
    ],
    '#default_value' => theme_get_setting('local_footer_variant_classname'),
  ];
}
