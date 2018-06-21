<?php

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
