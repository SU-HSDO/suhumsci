<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

function _su_humsci_profile_revert_configs(array $configs) {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');

  foreach ($configs as $config_type => $names) {
    foreach ($names as $name) {

      // Try to import if the config is new.
      try {
        $config_update->import($config_type, $name);
      }
      catch (Exception $e) {
        \Drupal::logger('su_humsci_profile')->info($e->getMessage());
      }

      // The config now exists, so lets revert to make sure we're in the correct
      // state. We do this because if its an entity view display, layout builder
      // modifies the configuration on creation. We don't want that.
      try {
        $config_update->revert($config_type, $name);
      }
      catch (Exception $e) {
        \Drupal::logger('su_humsci_profile')->error($e->getMessage());
      }
    }
  }
}

/**
 * Release 8.0.1 changes.
 */
function su_humsci_profile_post_update_8_0_1() {
  \Drupal::service('module_installer')->install(['config_update']);

  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');

  // New items.
  $config_update->import('entity_view_mode', 'node.hs_vertical_card');
  $config_update->import('system.simple', 'ds.field.hs_event_day');
  $config_update->import('system.simple', 'ds.field.hs_event_time');
  $config_update->import('system.simple', 'ds.field.hs_event_time_range');
  $config_update->import('entity_view_display', 'node.hs_event.hs_vertical_card');
  $config_update->import('entity_view_display', 'node.hs_publications.hs_vertical_card');

  // Revert items.
  $config_update->revert('entity_view_display', 'node.hs_event.hs_horizontal_card');
  $config_update->revert('entity_view_display', 'paragraph.hs_view.default');
  $config_update->revert('view', 'hs_publications');
  $config_update->revert('view', 'hs_events');
}

/**
 * Release 8.0.2 changes.
 */
function su_humsci_profile_post_update_8_0_2() {
  // Removed this as it is now handled by config management.
}
