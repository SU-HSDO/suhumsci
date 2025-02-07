<?php

/**
 * @file
 * Views data.
 */

/**
 * Implements hook_views_data().
 */
function hs_migrate_views_data() {
  $data = [];

  $data['node']['entity_migration'] = [
    'title' => t('Content Migration'),
    'help' => t('Label of the migration used on the item.'),
    'group' => t('Content'),
    'field' => ['id' => 'entity_migration'],
  ];


  return $data;
}
