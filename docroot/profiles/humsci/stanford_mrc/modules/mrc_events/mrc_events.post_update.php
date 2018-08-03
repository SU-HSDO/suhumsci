<?php

/**
 * mrc_events.post_update.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\menu_position\Entity\MenuPositionRule;
use Drupal\eck\Entity\EckEntityType;

/**
 * @param string $entity_type
 * @param string $bundle
 * @param string $field_name
 * @param string $type
 * @param string $label
 * @param int $cardinality
 */
function mrc_events_create_field($entity_type, $bundle, $field_name, $type, $label, $cardinality = -1) {
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
 * Reverts the view.
 */
function mrc_events_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_events',
  ];
  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_events') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);
}

/**
 * Enable new module and revert view.
 * Changes to events node.
 */
function mrc_events_post_update_8_0_5() {
  \Drupal::service('module_installer')
    ->install(['views_taxonomy_term_name_depth']);
  $configs = [
    'views.view.mrc_events',
    'core.entity_view_display.node.stanford_event.default',
  ];
  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_events') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('core.entity_form_display.node.stanford_event.default');
  $config->set('content.field_s_event_date.settings.increment', 15);
  $config->save();
}

/**
 * Create new image style.
 */
function mrc_events_post_update_8_0_6() {
  \Drupal::service('module_installer')->install(['focal_point']);
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->import('image_style', 'event_350');
}

/**
 * Revert the events display.
 */
function mrc_events_post_update_8_0_7_alpha1() {
  \Drupal::service('module_installer')->install(['menu_position', 'eck']);
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('node_type', 'stanford_event');

  if (!MenuPositionRule::load('events')) {
    $config_update->import('menu_position_rule', 'events');
  }
  $config_update->revert('menu_position_rule', 'events');

  if (!EckEntityType::load('event_collections')) {
    $config_update->import('eck_entity_type', 'event_collections');
    $config_update->import('event_collections_type', 'speaker');
  }


  mrc_events_create_field('node', 'stanford_event', 'field_s_event_speaker', 'bricks', 'Speaker');
  $config_update->revert('field_storage_config', 'node.field_s_event_speaker');
  $config_update->revert('field_config', 'node.stanford_event.field_s_event_speaker');
  $config_update->revert('entity_form_display', 'node.stanford_event.default');
  $config_update->revert('entity_view_display', 'node.stanford_event.default');
}

/**
 * Release 8.0.8 changes.
 */
function mrc_events_post_update_8_0_8() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('view', 'mrc_events');
}
