<?php

/**
 * @file
 * stanford_mrc.profile
 */

/**
 * Implements hook_install_tasks_alter().
 */
function stanford_mrc_install_tasks_alter(&$tasks, $install_state) {
  $tasks['after_install'] = [
    'display_name' => t('Resave Install Configs'),
    'display' => FALSE,
    'function' => 'stanford_mrc_after_install',
  ];
}
