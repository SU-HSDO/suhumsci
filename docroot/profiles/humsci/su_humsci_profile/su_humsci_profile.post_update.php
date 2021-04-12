<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

/**
 * Implements hook_removed_post_updates().
 */
function su_humsci_profile_removed_post_updates() {
  return [
    'su_humsci_profile_post_update_8222' => '9.x-1.1',
    'su_humsci_profile_post_update_8230' => '9.x-1.1',
    'su_humsci_profile_post_update_8280' => '9.x-1.1',
    'su_humsci_profile_post_update_8290' => '9.x-1.1',
  ];
}

/**
 * Disable the new timeline paragraph type on older themes.
 */
function su_humsci_profile_post_update_9001() {
  $theme = \Drupal::config('system.theme')->get('default');
  $newer_themes = [
    'humsci_airy',
    'humsci_basic',
    'humsci_colorful',
    'humsci_traditional',
  ];

  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->load('node.hs_basic_page.field_hs_page_components');
  $settings = $field->getSettings();
  $settings['handler_settings']['target_bundles']['hs_timeline_item'] = 'hs_timeline_item';
  $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline_item'] = [
    'enabled' => TRUE,
    'weight' => 99,
  ];

  if (!in_array($theme, $newer_themes)) {
    $settings['handler_settings']['target_bundles']['hs_timeline'] = 'hs_timeline';
    $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline'] = $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline_item'];
  }
  $field->set('settings', $settings);
  $field->save();

  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->load('paragraph.hs_row.field_hs_row_components');
  $settings = $field->getSettings();
  $settings['handler_settings']['target_bundles']['hs_timeline_item'] = 'hs_timeline_item';
  $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline_item'] = [
    'enabled' => TRUE,
    'weight' => 99,
  ];

  if (!in_array($theme, $newer_themes)) {
    $settings['handler_settings']['target_bundles']['hs_timeline'] = 'hs_timeline';
    $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline'] = $settings['handler_settings']['target_bundles_drag_drop']['hs_timeline_item'];
  }
  $field->set('settings', $settings);
  $field->save();
}

/**
 * Set the source plugin value on migration configs.
 */
function su_humsci_profile_post_update_9002() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('migrate_plus.migration.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    if (!$config->get('source.plugin')) {
      $config->set('source.plugin', 'url')->save();
    }
  }
}
