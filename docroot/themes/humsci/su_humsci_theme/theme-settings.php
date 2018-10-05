<?php

/**
 * @file
 * Provides an additional config form for theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * Form override for theme settings.
 */
function su_humsci_theme_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  $form['humsci_settings'] = [
    '#type' => 'details',
    '#title' => t('Humsci Settings'),
    '#open' => TRUE,
  ];
  $form['humsci_settings']['humsci_site_styles'] = [
    '#type' => 'select',
    '#title' => t('Choose a site style'),
    '#empty_option' => t('- Default -'),
    '#default_value' => theme_get_setting('humsci_site_styles'),
    '#options' => [
      'archaeology' => t('Archaeology'),
      'francestanford' => t('France Stanford'),
    ],
  ];

  stanford_basic_form_system_theme_settings_alter($form, $form_state);
}
