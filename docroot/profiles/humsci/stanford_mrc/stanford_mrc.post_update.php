<?php

use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\Role;
use Drupal\file\Entity\File;
use Drupal\search\Entity\SearchPage;

/**
 * Release 8.0.4 changes.
 */
function stanford_mrc_post_update_8_0_4() {
  // No Longer needed.
}

/**
 * Release 8.0.5 changes.
 */
function stanford_mrc_post_update_8_0_5() {
  // No Longer needed.
}

/**
 * Release 8.0.6 changes.
 */
function stanford_mrc_post_update_8_0_6() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function stanford_mrc_post_update_8_0_6__1() {
  // No Longer needed.

}

/**
 * No Longer needed.
 */
function stanford_mrc_post_update_8_0_6__2() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function stanford_mrc_post_update_8_0_6__3() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function _stanford_mrc_post_update_get_fields() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function _stanford_mrc_post_update_migrate_file() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function _stanford_mrc_post_update_migrate_video() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function _stanford_mrc_post_update_find_media_file() {
  // No Longer needed.
}

/**
 * No Longer needed.
 */
function _stanford_mrc_post_update_find_media_video() {
  // No Longer needed.
}

/**
 * Release 8.0.7-alpha1 Changes.
 */
function stanford_mrc_post_update_8_0_7_alpha1() {
  // No Longer needed.
}

/**
 * Release 8.0.7 Changes.
 */
function stanford_mrc_post_update_8_0_7() {
  // No Longer needed.
}

/**
 * Release 8.0.8 Changes.
 */
function stanford_mrc_post_update_8_0_8() {
  // No Longer needed.
}

/**
 * Switch from mrc_media to stanford_media.
 */
function stanford_mrc_post_update_8_0_8__1() {
  // No Longer needed.
}

/**
 * Release 8.0.8 Production changes.
 */
function stanford_mrc_post_update_8_0_8_prod() {
  // No Longer needed.
}

/**
 * Release 8.0.9 changes.
 */
function stanford_mrc_post_update_8_0_9() {
  // No Longer needed.
}

/**
 * Release 8.0.9 changes.
 */
function stanford_mrc_post_update_8_0_10_alpha1() {
  // No Longer needed.
}

/**
 * Use more of the product code.
 */
function stanford_mrc_post_update_8_0_11() {
  /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->install(['hs_field_helpers']);
  $module_installer->uninstall(['mrc_migrate_processors']);
}
