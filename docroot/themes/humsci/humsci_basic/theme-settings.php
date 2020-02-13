<?php

/**
 * @file
 * Provides an additional config form for theme settings.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;


// Set theme name to use in the key values.
$theme_name = \Drupal::theme()->getActiveTheme()->getName();

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * Form override for theme settings.
 */
function humsci_basic_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  $form['options_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Theme Specific Settings'),
  ];

  // Brandbar
  $form['options_settings']['humsci_basic_brand_bar'] = [
    '#type' => 'fieldset',
    '#title' => t('Brand Bar Settings'),
  ];

  $form['options_settings']['humsci_basic_brand_bar']['brand_bar_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Brand Bar Variant'),
    '#options' => [
      'default' => '- Default -',
      'bright' => t('Bright'),
      'dark' => t('Dark'),
      'white' => t('White'),
    ],
    '#default_value' => theme_get_setting('brand_bar_variant_classname'),
  ];
}
