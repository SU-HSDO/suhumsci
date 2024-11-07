<?php

/**
 * @file
 * hs_courses_importer.post_update.php
 */

/**
 * Implements hook_removed_post_updates().
 */
function hs_courses_importer_removed_post_updates() {
  return [
    'hs_courses_importer_post_update_8001' => '8.3.0',
    'hs_courses_importer_post_update_8002' => '8.3.0',
    'hs_courses_importer_post_update_8003' => '8.3.0',
  ];
}
