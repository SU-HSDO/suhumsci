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
function humsci_colorful_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  // Local Footer
  $form['options_settings']['humsci_basic_local_footer'] = [
    '#type' => 'fieldset',
    '#title' => t('Local Footer Settings'),
  ];

  $form['options_settings']['humsci_basic_local_footer']['local_footer_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Local Footer Variant'),
    '#options' => [
      'default' => '- Default -',
      'dark' => t('Dark'),
    ],
    '#default_value' => theme_get_setting('local_footer_variant_classname'),
  ];
}
