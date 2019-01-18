<?php

/**
 * @file
 * su_humsci_profile.profile
 */

use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_menu_link_content_menu_link_content_form_alter(array &$form, FormStateInterface $form_state) {
  $form['link']['widget'][0]['uri']['#description']['#items'][] = t('Enter "@text" for a menu item that is not clickable.', ['@text' => 'route:<nolink>']);
}

/**
 * Implements hook_install_tasks_alter().
 */
function su_humsci_profile_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_finished']['function'] = 'su_humsci_profile_lock_config';
}

/**
 * Implements hook_link_alter().
 */
function su_humsci_profile_link_alter(&$variables) {
  if ($variables['url']->isRouted() && ($variables['url']->getRouteName() == 'entity.user.collection' || $variables['url']->getRouteName() == 'user.admin_index')) {
    $variables['text'] = 'Users';
  }
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

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_user_admin_permissions_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\user\PermissionHandler $permission_handler */
  $permission_handler = \Drupal::service('user.permissions');
  $roles = array_keys(Role::loadMultiple());
  if (\Drupal::currentUser()->id() == 1) {
    return;
  }

  // Disables the UI from adding permissions that are potentially site breaking.
  // This might need adjustment if it becomes a problem. Permissions can still
  // be changed via drush or update hooks.
  foreach ($permission_handler->getPermissions() as $permission_name => $permission) {
    if (isset($permission['restrict access']) && $permission['restrict access']) {
      foreach ($roles as $role) {
        if (isset($form['permissions'][$permission_name][$role])) {
          $form['permissions'][$permission_name][$role]['#attributes']['disabled'] = TRUE;
        }
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_block_form_alter(array &$form, FormStateInterface $form_state) {
  su_humsci_profile_simplify_condition_forms($form['visibility'], $form, $form_state);
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_menu_position_rule_form_alter(array &$form, FormStateInterface $form_state) {
  su_humsci_profile_simplify_condition_forms($form['conditions'], $form, $form_state);
}

/**
 * Loop through a conditions for element and remove all the bad items.
 *
 * Condition plugins are very powerful and therefore are very delicate when
 * trying to configure them in the UI. Almost all of them are unnecessary to the
 * end user. Also many of them, especially from ctools and rules modules, cause
 * unwanted condition data to be stored on the configuration entities. This
 * in turn causes unwanted reactions when validating the plugins. So to prevent
 * that from happening, we're going to strip out all of the conditions except
 * the few that are necessary.
 *
 * @param array $condition_elements
 *   Form element array.
 * @param array $complete_form
 *   Complete form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current form state.
 */
function su_humsci_profile_simplify_condition_forms(array &$condition_elements, array $complete_form, FormStateInterface $form_state) {
  // Allow User 1 to view and modify any conditions.
  if (\Drupal::currentUser()->id() == 1) {
    return;
  }

  $good_plugins = [
    'node_type',
    'request_path',
    'user_role',
    'entity_bundle:node',
    'current_theme',
  ];
  /** @var \Drupal\Core\Condition\ConditionManager $condition_manager */
  $condition_manager = \Drupal::service('plugin.manager.condition');

  // Loop through the condition plugin definitions and trim out bad ones.
  foreach (array_keys($condition_manager->getDefinitions()) as $plugin_id) {
    if (!in_array($plugin_id, $good_plugins)) {
      unset($condition_elements[$plugin_id]);
    }
  }
  // Ctools has an identical plugin that core provides, except ctools has the
  // "Negate the Condition" checkbox so it's a little more flexible.
  if (isset($condition_elements['entity_bundle:node'])) {
    unset($condition_elements['node_type']);
  }
}

/**
 * Implements hook_ENTITY_TYPE_insert().
 */
function hs_field_helpers_eck_entity_type_insert(EntityInterface $entity) {
  $eck_type = $entity->id();
  // When a new ECK entity type is create, set initial permissions so that
  // site builders aren't required to search for the necessary permissions.
  user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ["view any $eck_type entities"]);
  user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ["view any $eck_type entities"]);

  user_role_grant_permissions('contributor', [
    "create $eck_type entities",
    "delete own $eck_type entities",
    "edit own $eck_type entities",
  ]);
  user_role_grant_permissions('site_manager', [
    "create $eck_type entities",
    "delete any $eck_type entities",
    "edit any $eck_type entities",
  ]);
}
