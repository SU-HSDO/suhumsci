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
 * Disable a paragraph type from the component field on flexible pages.
 *
 * @param string $paragraph_type
 *   Paragraph machine name.
 * @param bool $all_themes
 *   If the paragraph should be disabled on EVERY theme.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _humsci_profile_disable_page_paragraph($paragraph_type, $all_themes = TRUE) {
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

  if ($all_themes || !in_array($theme, $newer_themes)) {
    _humsci_profile_disable_paragraph_on_field($field, $paragraph_type);
  }
}

/**
 * Disable a paragraph type from the component field on row paragraphs.
 *
 * @param string $paragraph_type
 *   Paragraph machine name.
 * @param bool $all_themes
 *   If the paragraph should be disabled on EVERY theme.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _humsci_profile_disable_row_paragraph($paragraph_type, $all_themes = TRUE) {
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
  _humsci_profile_disable_page_paragraph('hs_timeline_item');
  _humsci_profile_disable_row_paragraph('hs_timeline_item');
  _humsci_profile_disable_page_paragraph('hs_timeline', FALSE);
  _humsci_profile_disable_row_paragraph('hs_timeline');
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
  _humsci_profile_disable_page_paragraph('hs_collection');
  _humsci_profile_disable_row_paragraph('hs_collection');
}

/**
 * Disable the callout box paragraph type on older themes and rows.
 */
function su_humsci_profile_post_update_9005() {
  _humsci_profile_disable_page_paragraph('hs_callout_box', FALSE);
  _humsci_profile_disable_row_paragraph('hs_callout_box');
}

/**
 * Disable the gradient hero paragraph type on all themes and rows.
 */
function su_humsci_profile_post_update_9006() {
  _humsci_profile_disable_page_paragraph('hs_gradient_hero');
  _humsci_profile_disable_row_paragraph('hs_gradient_hero');
}

/**
 * Uninstall printfriendly module.
 */
function su_humsci_profile_post_update_9007() {
  \Drupal::service('module_installer')->uninstall(['printfriendly']);
  \Drupal::configFactory()
    ->getEditable('views.view.conference_agenda')
    ->delete();
}

/**
 * Disable the gradient hero slider paragraph type on all themes, updated by 9010.
 */
function su_humsci_profile_post_update_9008() {
  _humsci_profile_disable_page_paragraph('hs_gradient_hero_slider');
  _humsci_profile_disable_row_paragraph('hs_gradient_hero_slider');
}

/**
 * Disable the color band paragraph type on older themes and older theme rows only.
 */
function su_humsci_profile_post_update_9009() {
  _humsci_profile_disable_page_paragraph('hs_clr_bnd', FALSE);
  _humsci_profile_disable_row_paragraph('hs_clr_bnd');
}

/**
 * Disable the gradient hero slider paragraph type on older themes and older theme rows.
 */
function su_humsci_profile_post_update_9010() {
  _humsci_profile_disable_page_paragraph('hs_gradient_hero_slider', FALSE);
  _humsci_profile_disable_row_paragraph('hs_gradient_hero_slider', FALSE);
}
