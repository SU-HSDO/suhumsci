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
  $db->query("INSERT INTO paragraph__field_title_settings (entity_id, revision_id, langcode, delta, bundle, field_title_settings_value)
    SELECT id, revision_id, langcode, 0, 'hs_collection', 'collection_title_none'
    FROM paragraphs_item_field_data
    WHERE type = 'hs_collection'");
}
