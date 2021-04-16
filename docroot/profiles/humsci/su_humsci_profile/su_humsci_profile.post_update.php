<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\field\FieldConfigInterface;

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
 * Disable a new paragraph type from being available in the UI.
 *
 * @param string $paragraph_type
 *   New paragraph type name.
 * @param bool $all_themes
 *   If the paragraph should be disabled on every theme, false if only legacy.
 * @param bool $only_rows
 *   True if disable it on row components but enabled on regular fields.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _humsci_profile_disable_paragraph_type($paragraph_type, $all_themes = TRUE, $only_rows = FALSE) {
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

  if (!$only_rows && ($all_themes || !in_array($theme, $newer_themes))) {
    _humsci_profile_disable_paragraph_on_field($field, $paragraph_type);
  }

  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->load('paragraph.hs_row.field_hs_row_components');
  if ($all_themes || !in_array($theme, $newer_themes)) {
    _humsci_profile_disable_paragraph_on_field($field, $paragraph_type);
  }
}

/**
 * Disable the paragraph type on the give field config entity.
 *
 * @param \Drupal\field\FieldConfigInterface $field_config
 *   Field entity.
 * @param string $paragraph_type
 *   Paragraph machine name.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _humsci_profile_disable_paragraph_on_field(FieldConfigInterface $field_config, $paragraph_type) {
  $settings = $field_config->getSettings();
  $settings['handler_settings']['target_bundles'][$paragraph_type] = $paragraph_type;
  $settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type] = [
    'enabled' => TRUE,
    'weight' => 99,
  ];
  $field_config->set('settings', $settings);
  $field_config->save();
}

/**
 * Disable the new timeline paragraph type on older themes.
 */
function su_humsci_profile_post_update_9001() {
  _humsci_profile_disable_paragraph_type('hs_timeline_item', FALSE);
  _humsci_profile_disable_paragraph_type('hs_timeline', FALSE);
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

/**
 * Disable the new collection paragraph type on rows.
 */
function su_humsci_profile_post_update_9004() {
  _humsci_profile_disable_paragraph_type('hs_collection', TRUE);
}
