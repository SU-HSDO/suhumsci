<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\field\Entity\FieldConfig;

/**
 * Implements hook_removed_post_updates().
 */
function su_humsci_profile_removed_post_updates() {
  return [
    'su_humsci_profile_post_update_8222' => '9.x-1.1',
    'su_humsci_profile_post_update_8230' => '9.x-1.1',
    'su_humsci_profile_post_update_8280' => '9.x-1.1',
    'su_humsci_profile_post_update_8290' => '9.x-1.1',
    'su_humsci_profile_post_update_9001' => '9.x-1.5',
    'su_humsci_profile_post_update_9002' => '9.x-1.5',
    'su_humsci_profile_post_update_9004' => '9.x-1.5',
    'su_humsci_profile_post_update_9005' => '9.x-1.5',
    'su_humsci_profile_post_update_9006' => '9.x-1.5',
    'su_humsci_profile_post_update_9007' => '9.x-1.5',
    'su_humsci_profile_post_update_9008' => '9.x-1.5',
    'su_humsci_profile_post_update_9009' => '9.x-1.5',
    'su_humsci_profile_post_update_9010' => '9.x-1.5',
  ];
}

/**
 * Disable a paragraph type on a field for an entity type.
 *
 * @param string $entity_type
 *   Entity type id.
 * @param string $bundle
 *   Entity bundle id.
 * @param string $field_name
 *   Field machine name.
 * @param string $paragraph_type
 *   Paragraph type id.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _su_humsci_profile_disable_paragraph($entity_type, $bundle, $field_name, $paragraph_type) {
  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = FieldConfig::load("$entity_type.$bundle.$field_name");
  $settings = $field->getSettings();
  $settings['handler_settings']['target_bundles'][$paragraph_type] = $paragraph_type;
  $settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type] = [
    'enabled' => $settings['handler_settings']['negate'] ? TRUE : FALSE,
    'weight' => 99,
  ];
  $field->set('settings', $settings);
  $field->save();
}

/**
 * Enables a paragraph type on a field for an entity type.
 *
 * @param string $entity_type
 *   Entity type id.
 * @param string $bundle
 *   Entity bundle id.
 * @param string $field_name
 *   Field machine name.
 * @param string $paragraph_type
 *   Paragraph type id.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _su_humsci_profile_enable_paragraph($entity_type, $bundle, $field_name, $paragraph_type, $weight = 0) {
  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = FieldConfig::load("$entity_type.$bundle.$field_name");
  $settings = $field->getSettings();

  $settings['handler_settings']['target_bundles'][$paragraph_type] = $paragraph_type;
  if ($settings['handler_settings']['negate']) {
    unset($settings['handler_settings']['target_bundles'][$paragraph_type]);
  }

  $settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type] = [
    'enabled' => $settings['handler_settings']['negate'] ? FALSE : TRUE,
    'weight' => $weight,
  ];
  $field->set('settings', $settings);
  $field->save();
}

/**
 * Is the current theme one of the legacy themes.
 *
 * @return bool
 *   True if the active theme is one of the older ones.
 */
function _su_humsci_profile_is_legacy_theme() {
  $current_theme = \Drupal::config('system.theme')->get('default');
  $new_themes = \Drupal::config('su_humsci_profile.settings')
    ->get('new_themes');
  return !in_array($current_theme, $new_themes);
}

/**
 * Disable/enable paragraph types.
 */
function su_humsci_profile_post_update_9011() {
  if (_su_humsci_profile_is_legacy_theme()) {
    _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_timeline');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_timeline');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_timeline');

    _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_timeline_item');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_timeline_item');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_timeline_item');

    _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_gradient_hero_slider');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_gradient_hero_slider');
    _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_gradient_hero_slider');
    return;
  }

  _su_humsci_profile_enable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_timeline');
  _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_timeline_item');

  _su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_gradient_hero_slider');
  _su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_gradient_hero_slider');
}

/**
 * Delete accordion paragraph field.
 */
function su_humsci_profile_post_update_9012() {
  $field = FieldConfig::loadByName('paragraph', 'hs_accordion', 'field_hs_accordion_image');
  if ($field) {
    $field->delete();
  }
}
