<?php

/**
 * @file
 * su_humsci_profile.profile
 */

use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\block\Entity\Block;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
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
 * Implements hook_ENTITY_TYPE_insert().
 */
function su_humsci_profile_menu_link_content_presave(MenuLinkContentInterface $entity) {
  // For new menu link items created on a node form (normally), set the expanded
  // attribute so all menu items are expanded by default.
  if ($entity->isNew()) {
    $entity->set('expanded', TRUE);
  }
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
 * Implements hook_block_access().
 */
function su_humsci_profile_block_access(Block $block, $operation, AccountInterface $account) {
  $current_request = \Drupal::requestStack()->getCurrentRequest();
  // Disable the page title block on 404 page IF the page is a node. Nodes
  // should have the page title displayed in the node display configuration so
  // we can rely on that.
  if ($block->getPluginId() == 'page_title_block' && $current_request->query->get('_exception_statuscode') == 404) {
    return AccessResult::forbiddenIf($current_request->attributes->get('node'))
      ->addCacheableDependency($block);
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
