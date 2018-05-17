<?php

/**
 * @file
 * su_humsci_profile.profile
 */

/**
 * Implements hook_install_tasks_alter().
 */
function su_humsci_profile_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_finished'][] = [
    'function' => 'su_humsci_profile_lock_config',
  ];
}
