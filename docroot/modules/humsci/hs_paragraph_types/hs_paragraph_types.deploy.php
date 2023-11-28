<?php

/**
 * @file
 * hs_paragraph_types.deploy.php
 */

/**
 * Set default field_title_settings value for existing content.
 */
function hs_paragraph_types_deploy_a_field_title_settings() {
  $db = \Drupal::database();
  $collections = $db->select('paragraphs_item_field_data', 'p')
    ->fields('p', ['id', 'revision_id', 'langcode'])
    ->condition('type', 'hs_collection')
    ->execute();

  foreach ($collections as $collection) {
    $db->upsert('paragraph__field_title_settings')
    ->fields([
      'entity_id' => $collection->id,
      'revision_id' => $collection->revision_id,
      'field_title_settings_value' => 'I do not want a heading for this Collection',
      'delta' => 0,
      'langcode' => $collection->langcode,
      'bundle' => 'hs_collection',
    ])
    ->key($collection->id)
    ->execute();
  }
}
