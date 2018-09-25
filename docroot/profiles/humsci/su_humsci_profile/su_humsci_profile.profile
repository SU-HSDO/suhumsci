<?php

/**
 * @file
 * su_humsci_profile.profile
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Implements hook_install_tasks_alter().
 */
function su_humsci_profile_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_finished']['function'] = 'su_humsci_profile_lock_config';
}

/**
 * Implements hook_local_tasks_alter().
 */
function su_humsci_profile_local_tasks_alter(&$local_tasks) {
  unset($local_tasks['user.pass']);
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_user_login_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['simplesamlphp_auth_login_link'])) {
    // Moves the original form elements into a collapsed group.
    $form['simplesamlphp_auth_login_link']['#weight'] = -99;
    $form['manual'] = [
      '#type' => 'details',
      '#title' => t('Manual Login'),
      '#open' => FALSE,
    ];
    $form['manual']['name'] = $form['name'];
    $form['manual']['pass'] = $form['pass'];
    $form['manual']['actions'] = $form['actions'];
    $form['manual']['actions']['reset'] = Link::createFromRoute(t('Reset Password'), 'user.pass')
      ->toRenderable();
    unset($form['name'], $form['pass'], $form['actions']);
  }
}

/**
 * Implements hook_entity_operation_alter().
 */
function su_humsci_profile_entity_operation_alter(array &$operations, EntityInterface $entity) {
  $role_delegation = \Drupal::moduleHandler()->moduleExists('role_delegation');
  if ($entity instanceof User && $role_delegation) {
    $operations['roles'] = [
      'title' => t('Manage Roles'),
      'weight' => 11,
      'url' => Url::fromRoute('role_delegation.edit_form', ['user' => $entity->id()]),
    ];
  }
}
