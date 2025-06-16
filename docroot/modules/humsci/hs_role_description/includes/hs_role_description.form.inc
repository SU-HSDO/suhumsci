<?php

/**
 * @file
 * Role description form.
 *
 * @category form
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function hs_role_description_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (
        \Drupal::moduleHandler()->moduleExists('role_delegation')
    &&  $form['role_change']['#access']
  ) {
    $element = &$form['role_change']['widget'];
  }
  else {
    $element = &$form['account']['roles'];
  }

  $role_description = \Drupal::config('hs_role_description.settings')->get('role_description');
  foreach ($role_description as $role => $description) {
    $element[$role]['#description'] = $description;
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function hs_role_description_form_role_delegation_role_assign_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $role_description = \Drupal::config('hs_role_description.settings')->get('role_description');
  foreach ($role_description as $role => $description) {
    $form['account']['role_change'][$role]['#description'] = $description;
  }
}
