<?php

/**
 * @file
 * su_humsci_profile.profile
 */

/**
 * Implements hook_install_tasks_alter().
 */
function su_humsci_profile_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_configure_form']['function'] = 'Drupal\\su_humsci_profil\\Form\\SiteConfigureForm';
}
