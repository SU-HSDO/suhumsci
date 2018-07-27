<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

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
  /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
  $module_installer = \Drupal::service('module_installer');
  // Install research first so the display settings for person content can get
  // installed.
  $module_installer->install(['hs_research']);
  $module_installer->install(['hs_person']);

  $configs = [
    'field_storage_config' => [
      'publications_collections.field_hs_publication_author',
    ],
    'field_config' => [
      'publications_collections.publication_author.field_hs_publication_author',
      'node.hs_person.field_hs_person_research',
    ],
    'entity_form_display' => [
      'publications_collections.publication_author.default',
    ],
    'entity_view_display' => [
      'publications_collections.publication_author.default',
    ],
    'view' => [
      'hs_publications',
    ],
  ];
  _su_humsci_profile_revert_configs($configs);
}

/**
 * Fixes layout builder patch differences.
 */
function su_humsci_profile_post_update_8_0_3() {
  /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $display */
  foreach (EntityViewDisplay::loadMultiple() as $display) {
    $lb_settings = $display->getThirdPartySettings('layout_builder');
    if (empty($lb_settings)) {
      continue;
    }

    // https://www.drupal.org/files/issues/2018-05-03/2936358-opt_in-32.patch
    $old_key = $display->getThirdPartySetting('layout_builder', 'enable_defaults');
    // https://cgit.drupalcode.org/drupal/tree/core/modules/layout_builder/config/schema/layout_builder.schema.yml?id=c6cdc4392324b4ee18f0492bbc6ea53c2f918250
    $new_key = $display->getThirdPartySetting('layout_builder', 'enabled');

    // The layout builder is not enabled, so remove all the third party settings
    // from the config.
    if ($old_key === FALSE || $new_key === FALSE) {
      foreach (array_keys($lb_settings) as $key) {
        $display->unsetThirdPartySetting('layout_builder', $key);
      }
      $display->save();
      continue;
    }

    // Remove the old key.
    $display->unsetThirdPartySetting('layout_builder', 'enable_defaults');
    $display->save();
  }
}
