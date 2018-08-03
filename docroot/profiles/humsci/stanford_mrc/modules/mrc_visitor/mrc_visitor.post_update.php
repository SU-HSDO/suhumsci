<?php

/**
 * @file
 * mrc_visitor.post_update.php
 */

/**
 * Changes field settings on visitor & reverts the view.
 */
function mrc_visitor_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_visitor',
    'pathauto.pattern.mrc_visitors',
  ];

  \Drupal::service('module_installer')->install(['mrc_yearonly']);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_visitor') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);

  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Config $config_entity */
  $config_entity = $config_factory->getEditable('core.entity_view_display.node.stanford_visitor.default');
  $config_entity->set('content.field_s_visitor_year_visited.type', 'yearonly_academic');
  $config_entity->set('content.field_s_visitor_year_visited.settings.order', 'asc');
  $config_entity->set('hidden.field_mrc_event_series', 'true');
  $config_entity->save();

  // Save the pathauto pattern so that it's uuids correct and it applies.
  /** @var \Drupal\pathauto\Entity\PathautoPattern $entity */
  $entity = \Drupal::entityTypeManager()
    ->getStorage('pathauto_pattern')
    ->load('mrc_visitors');
  if ($entity) {
    $entity->save();
  }
}

/**
 * Release 8.0.7-alpha1 changes.
 */
function mrc_visitor_post_update_8_0_7_alpha1() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $file = drupal_get_path('module', 'mrc_visitor') . '/img/visitor-profile.png';
  $file = file_save_data(file_get_contents($file), 'public://visitor-profile.png');

  // Load the media type entity to get the source field.
  $media_type = $entity_type_manager->getStorage('media_type')
    ->load('image');
  $source_field = $media_type->getSource()
    ->getConfiguration()['source_field'];

  // Create the new media entity.
  $media_entity = $entity_type_manager->getStorage('media')
    ->create([
      'bundle' => $media_type->id(),
      $source_field => $file,
      'uid' => \Drupal::currentUser()->id(),
      'status' => TRUE,
      'type' => $media_type->getSource()->getPluginId(),
    ]);
  $media_entity->save();

  \Drupal::service('config.factory')
    ->getEditable('mrc_visitor.default_image')
    ->set('mid', $media_entity->id())
    ->save();

  $visitors = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadByProperties(['type' => 'stanford_visitor']);

  /** @var \Drupal\node\Entity\Node $visitor */
  foreach ($visitors as $visitor) {
    if ($visitor->get('field_mrc_image')->isEmpty()) {
      $visitor->save();
    }
  }
}

/**
 * Release 8.0.8 changes.
 */
function mrc_visitor_post_update_8_0_8() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('view', 'mrc_visitor');
  $config_update->revert('entity_view_display', 'node.stanford_visitor.default');
}

/**
 * Release 8.0.8 changes.
 */
function mrc_visitor_post_update_8_0_0() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('view', 'mrc_visitor');
}
