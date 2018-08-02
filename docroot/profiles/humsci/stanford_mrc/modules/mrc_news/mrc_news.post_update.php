<?php

/**
 * mrc_news.post_update.php
 */

/**
 * Revert the view.
 */
function mrc_news_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_news',
  ];

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_news') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);
}

/**
 * Create new image style.
 */
function mrc_news_post_update_8_0_6(){
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->import('image_style', 'news_thumbnail');
}

/**
 * Release 8.0.8 changes.
 */
function mrc_news_post_update_8_0_8() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('view', 'mrc_news');
  $config_update->revert('entity_form_display', 'node.stanford_news_item.default');
}
