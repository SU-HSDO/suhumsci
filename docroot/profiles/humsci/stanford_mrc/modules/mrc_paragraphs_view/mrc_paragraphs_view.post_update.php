<?php

/**
 * @file
 * mrc_paragraphs_view.post_update.php
 */

/**
 * Release 8.0.7 changes
 */
function mrc_paragraphs_view_post_update_8_0_7() {
  module_load_install('mrc_helper');
  mrc_helper_create_field('paragraph', 'mrc_view', 'field_mrc_view_title', 'string', 'Title', 1);

  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('field_storage_config', 'paragraph.field_mrc_view_title');
  $config_update->revert('field_config', 'paragraph.mrc_view.field_mrc_view_title');
  $config_update->revert('entity_form_display', 'paragraph.mrc_view.default');
  $config_update->revert('entity_view_display', 'paragraph.mrc_view.default');
}

/**
 * Release 8.0.7 changes
 */
function mrc_paragraphs_view_post_update_8_0_8() {
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('entity_view_display', 'paragraph.mrc_view.default');
}
