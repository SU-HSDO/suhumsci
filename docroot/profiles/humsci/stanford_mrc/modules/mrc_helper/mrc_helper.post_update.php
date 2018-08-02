<?php

/**
 * mrc_helper.post_update.php
 */

/**
 * Adds menu block to term pages.
 */
function mrc_helper_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block']);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_helper') . '/config/install';

  $configs = [
    'core.entity_view_display.taxonomy_term.mrc_event_series.default',
  ];
  stanford_mrc_update_configs(TRUE, $configs, $path);
}

/**
 * Create new view.
 */
function mrc_helper_post_update_8_0_6() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->import('view', 'mrc_event_series');
}

/**
 * Change menu links to entity reference links.
 */
function mrc_helper_post_update_8_0_7_alpha1() {
  \Drupal::service('module_installer')->install(['taxonomy_menu_ui']);
  $database = \Drupal::database();
  $links = $database->select('menu_link_content_data', 'm')
    ->fields('m', ['id', 'link__uri'])
    ->condition('link__uri', 'internal:%', 'LIKE')
    ->execute()
    ->fetchAllKeyed();
  foreach ($links as $id => $link) {
    $link = str_replace('internal:', '', $link);

    $source = $database->select('url_alias', 'u')
      ->fields('u', ['source'])
      ->condition('alias', $link)
      ->execute()
      ->fetchField();

    $source = trim($source, '/ ');
    list(, $type, $entity_id) = explode('/', $source);
    if ($type == 'term') {
      $new_link = "entity:taxonomy_term/$entity_id";
      $database->update('menu_link_content_data')
        ->fields(['link__uri' => $new_link])
        ->condition('id', $id)
        ->execute();
    }
  }

  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('entity_form_display', 'taxonomy_term.mrc_event_series.default');
}

/**
 * Update term display.
 */
function mrc_helper_post_update_8_0_8() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('entity_view_display','taxonomy_term.mrc_event_series.default');
}