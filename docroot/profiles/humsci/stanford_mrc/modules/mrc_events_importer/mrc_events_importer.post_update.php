<?php

/**
 * mrc_events_importer.post_update.php
 */

/**
 * Change importer urls.
 */
function mrc_events_importer_post_update_8_0_4() {
  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Config $config_entity */
  $config_entity = $config_factory->getEditable('migrate_plus.migration.events_image_importer');
  $config_entity->set('config.urls', 'http://events.stanford.edu/xml/drupal/v2.php?organization=631');
  $config_entity->save();

  $config_entity = $config_factory->getEditable('migrate_plus.migration.events_image_importer');
  $config_entity->set('config.urls', 'http://events.stanford.edu/xml/drupal/v2.php?organization=631');
  $config_entity->save();
}

/**
 * Release 8.0.6 Changes
 */
function mrc_events_importer_post_update_8_0_6() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('migration', 'events_image_importer');
  $config_update->revert('migration', 'events_importer');
}

/**
 * Release 8.0.7-alpha1 Changes
 */
function mrc_events_importer_post_update_8_0_7_alpha1(){
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('migration', 'events_image_importer');
  $config_update->revert('migration', 'events_importer');
}
