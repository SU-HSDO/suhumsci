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
  if ($settings['handler_settings']['negate']) {
    $settings['handler_settings']['target_bundles'][$paragraph_type] = $paragraph_type;
    $settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type] = [
      'enabled' => TRUE,
      'weight' => 99,
    ];
  }
  else {
    unset($settings['handler_settings']['target_bundles'][$paragraph_type]);
    unset($settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type]);
  }
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
    unset($settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type]);
  }
  else {
    $settings['handler_settings']['target_bundles_drag_drop'][$paragraph_type] = [
      'enabled' => TRUE,
      'weight' => $weight,
    ];
  }
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

/**
 * Disable private collections.
 */
function su_humsci_profile_post_update_9013() {
  _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_priv_collection');
  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_priv_collection');
  _su_humsci_profile_enable_paragraph('node', 'hs_private_page', 'field_hs_priv_page_components', 'hs_priv_collection');
}

/**
 * Disable Spotlight slider.
 */
function su_humsci_profile_post_update_9014() {
  _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_sptlght_slder');
  _su_humsci_profile_disable_paragraph('paragraph', 'hs_collection', 'field_hs_collection_items', 'hs_sptlght_slder');
  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_sptlght_slder');
  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_sptlght_slder');
  _su_humsci_profile_disable_paragraph('node', 'hs_private_page', 'field_hs_priv_page_components', 'hs_sptlght_slder');
}

/**
 * Convert spotlights to spotlight slideshows.
 */
function su_humsci_profile_post_update_9200() {
  if (_su_humsci_profile_is_legacy_theme()) {
    return;
  }
  $spotlights = \Drupal::entityTypeManager()
    ->getStorage('paragraph')
    ->loadByProperties(['type' => 'hs_spotlight']);

  /** @var \Drupal\paragraphs\ParagraphInterface $spotlight */
  foreach ($spotlights as $spotlight) {
    $parent_field = $spotlight->get('parent_field_name')->getString();
    $parent_type = $spotlight->get('parent_type')->getString();
    $parent_id = $spotlight->get('parent_id')->getString();

    if (!$parent_type || !\Drupal::entityTypeManager()
        ->hasDefinition($parent_type)) {
      continue;
    }
    $parent = \Drupal::entityTypeManager()
      ->getStorage($parent_type)
      ->load($parent_id);

    if (!$parent) {
      continue;
    }
    _su_humsci_profile_enable_paragraph($parent_type, $parent->bundle(), $parent_field, 'hs_sptlght_slder');
    _su_humsci_profile_disable_paragraph($parent_type, $parent->bundle(), $parent_field, 'hs_spotlight');

    $parent_values = $parent->get($parent_field)->getValue();

    $new_spotlight = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->create([
        'type' => 'hs_sptlght_slder',
        'field_hs_sptlght_sldes' => [
          [
            'target_id' => $spotlight->id(),
            'target_revision_id' => $spotlight->getRevisionId(),
          ],
        ],
      ]);
    $new_spotlight->save();

    foreach ($parent_values as &$value) {
      if ($value['target_id'] == $spotlight->id()) {
        $value = [
          'target_id' => $new_spotlight->id(),
          'target_revision_id' => $new_spotlight->getRevisionId(),
        ];
      }
    }
    $parent->set($parent_field, $parent_values)->save();
  }

  _su_humsci_profile_enable_paragraph('paragraph', 'hs_collection', 'field_hs_collection_items', 'hs_sptlght_slder');
  _su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_sptlght_slder');
  _su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_sptlght_slder');
  _su_humsci_profile_enable_paragraph('node', 'hs_private_page', 'field_hs_priv_page_components', 'hs_sptlght_slder');

  _su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_spotlight');
  _su_humsci_profile_disable_paragraph('paragraph', 'hs_collection', 'field_hs_collection_items', 'hs_spotlight');
  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_spotlight');
  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_hero', 'hs_spotlight');
  _su_humsci_profile_disable_paragraph('node', 'hs_private_page', 'field_hs_priv_page_components', 'hs_spotlight');
}

/**
 * Disable row and enable photo album.
 */
function su_humsci_profile_post_update_9201() {
  if (_su_humsci_profile_is_legacy_theme()) {
    return;
  }

  _su_humsci_profile_disable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_row');
  _su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'stanford_gallery');
}

/**
 * Delete any react pararaphs fields.
 */
function su_humsci_profile_post_update_9202() {
  $react_paragraphs_fields = [];
  foreach (FieldConfig::loadMultiple() as $field) {
    if (
      $field->getType() == 'entity_reference_revisions' &&
      $field->getSetting('handler') == 'default:paragraph_row'
    ) {
      $react_paragraphs_fields[$field->getName()] = $field;
    }
  }
  if ($react_paragraphs_fields) {
    $paragraphs = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->loadByProperties(['parent_field_name' => array_keys($react_paragraphs_fields)]);
    foreach ($paragraphs as $paragraph) {
      $paragraph->delete();
    }
  }
  $rows = \Drupal::entityTypeManager()
    ->getStorage('paragraph_row')
    ->loadMultiple();
  foreach ($rows as $row) {
    $row->delete();
  }
  foreach ($react_paragraphs_fields as $field) {
    $field->delete();
  }
  $row_types = \Drupal::entityTypeManager()
    ->getStorage('paragraphs_row_type')
    ->loadMultiple();
  foreach ($row_types as $row_type) {
    $row_type->delete();
  }
  \Drupal::service('module_installer')->uninstall(['react_paragraphs']);
}
