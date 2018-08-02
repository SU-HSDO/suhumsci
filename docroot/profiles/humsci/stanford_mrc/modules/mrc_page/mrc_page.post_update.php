<?php

/**
 * @file
 * mrc_page.post_update.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @param string $entity_type
 * @param string $bundle
 * @param string $field_name
 * @param string $type
 * @param string $label
 * @param int $cardinality
 */
function mrc_page_create_field($entity_type, $bundle, $field_name, $type, $label, $cardinality = -1) {
  $field_storage_config = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (empty($field_storage_config)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
    ])->save();
  }

  $field_instance = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (empty($field_instance)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
    ])->save();
  }
}


/**
 * Adds menu block to node display.
 */
function mrc_page_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block', 'block_field']);
  mrc_page_create_field('node', 'stanford_basic_page', 'field_s_mrc_page_sidebar_block', 'block_field', 'Sidebar Block', -1);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_page') . '/config/install';

  $configs = [
    'core.entity_view_display.node.stanford_basic_page.default',
    'core.entity_form_display.node.stanford_basic_page.default',
    'field.field.node.stanford_basic_page.field_s_mrc_page_sidebar_block',
    'field.storage.node.field_s_mrc_page_sidebar_block',
  ];
  stanford_mrc_update_configs(TRUE, $configs, $path);
}

/**
 * Change permissions on sidebar field.
 */
function mrc_page_post_update_8_0_6() {
  \Drupal::service('module_installer')->install(['field_permissions']);
  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_page') . '/config/install';

  $configs = [
    'field.storage.node.field_s_mrc_page_sidebar_block',
  ];
  stanford_mrc_update_configs(TRUE, $configs, $path);

  $roles = Role::loadMultiple([
    RoleInterface::ANONYMOUS_ID,
    RoleInterface::AUTHENTICATED_ID,
  ]);

  $add_permission = [
    'view field_s_mrc_page_sidebar_block',
    'view own field_s_mrc_page_sidebar_block',
  ];

  foreach ($roles as $role) {
    foreach ($add_permission as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
  }

}
