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
use Drupal\menu_position\Entity\MenuPositionRule;

/**
 * Implements hook_preprocess_HOOK().
 */
function su_humsci_profile_preprocess_table(&$variables) {
  if (!empty($variables['attributes']['id']) && $variables['attributes']['id'] == 'menu-link-weight-reorder') {
    foreach ($variables['rows'] as $key => &$row) {
      if (!empty($row['cells'][0]['content']['#title'])) {
        if ((string) $row['cells'][0]['content']['#title'] == 'Inaccessible') {
          unset($variables['rows'][$key]);
        }
      }
    }
  }
}

/**
 * Implements hook_contextual_links_alter().
 */
function su_humsci_profile_contextual_links_alter(array &$links, $group, array $route_parameters) {
  if ($group == 'paragraph') {
    // Paragraphs edit module clone link does not function correctly. Remove it
    // from available links. Also remove delete to avoid unwanted delete.
    unset($links['paragraphs_edit.delete_form']);
    unset($links['paragraphs_edit.clone_form']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_menu_link_content_menu_link_content_form_alter(array &$form, FormStateInterface $form_state) {
  $form['link']['widget'][0]['uri']['#description']['#items'][] = t('Enter "@text" for a menu item that is not clickable.', ['@text' => 'route:<nolink>']);
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
 * Implements hook_ENTITY_TYPE_insert().
 */
function su_humsci_profile_menu_link_content_insert(MenuLinkContentInterface $entity) {
  /** @var \Drupal\menu_position\MenuPositionRuleInterface $menu_position */
  foreach (MenuPositionRule::loadMultiple() as $menu_position) {
    if ($menu_position->getParent() == 'menu_link_content:' . $entity->uuid()) {
      \Drupal::database()->update('menu_tree')
        ->fields(['parent' => $menu_position->getParent()])
        ->condition('menu_name', $menu_position->getMenuName())
        ->condition('id', $menu_position->getMenuLink())
        ->execute();
    }
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
          $form['permissions'][$permission_name][$role]['#value'] = $form['permissions'][$permission_name][$role]['#default_value'] ?? 0;
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
function su_humsci_profile_eck_entity_type_insert(EntityInterface $entity) {
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

/**
 * Implements hook_preprocess_HOOK().
 */
function su_humsci_profile_preprocess_menu(&$variables) {
  if ($variables['menu_name'] != 'shortcut_menu') {
    return;
  }

  $current_user = \Drupal::currentUser();
  _su_humci_profile_clean_shortcut_links($variables['items'], $current_user);
}

/**
 * Recursively remove links the current user has no access to.
 *
 * @param array $links
 *   Menu links.
 * @param \Drupal\Core\Session\AccountInterface $current_user
 *   Current user object.
 */
function _su_humci_profile_clean_shortcut_links(array &$links, AccountInterface $current_user) {
  foreach ($links as $link_id => $link_item) {

    // This user doesn't have permission for this url. Remove the link.
    if (!empty($link_item['url']) && !$link_item['url']->access($current_user)) {
      unset($links[$link_id]);
      continue;
    }

    // User has access to the parent menu link, but check all the children.
    if (!empty($links[$link_id]['below'])) {
      _su_humci_profile_clean_shortcut_links($links[$link_id]['below'], $current_user);
    }
  }
}

/**
 * Implements hook_toolbar_alter().
 */
function su_humsci_profile_toolbar_alter(&$items) {
  $user_roles = \Drupal::currentUser()->getRoles();
  if (!in_array('administrator', $user_roles)) {
    unset($items['acquia_connector']);
  }
}

/**
 * Implements hook_page_attachments().
 */
function su_humsci_profile_page_attachments(array &$attachments) {
  $current_user = \Drupal::currentUser();
  // Hide the manage button in the toolbar if the user doesnt have permission.
  // Also don't add the library if user doesn't doesnt have access to the
  // toolbar.
  if ($current_user->hasPermission('access toolbar') && !$current_user->hasPermission('view toolbar manage')) {
    // HSD8-771 Roll back hide manage toolbar. Lets keep this here in case we
    // come back to it at a later date.
    // $attachments['#attached']['library'][] = 'su_humsci_profile/hide_manage';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_key_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (empty($form['settings']['provider_section']['key_provider']['#default_value'])) {
    return;
  }
  // Obscure the encrypted config values.
  if ($form['settings']['provider_section']['key_provider']['#default_value'] == 'encrypted_config') {
    $form['settings']['input_section']['key_input_settings']['key_value']['#type'] = 'password';
    $form['settings']['input_section']['key_input_settings']['key_value']['#attributes']['disabled'] = TRUE;
  }
}

/**
 * Implements hook_config_readonly_whitelist_patterns().
 */
function su_humsci_profile_config_readonly_whitelist_patterns() {
  return [
    'field.field.node.hs_basic_page.field_hs_page_components',
    'field.field.node.hs_basic_page.field_hs_page_hero',
    'field.field.node.hs_private_page.field_hs_priv_page_components',
  ];
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_node_type_add_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Disable preview mode and prevent it from being changed.
  $form['submission']['preview_mode']['#default_value'] = DRUPAL_DISABLED;
  $form['submission']['preview_mode']['#attributes']['disabled'] = TRUE;
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_media_library_add_form_embeddable_alter(array &$form, FormStateInterface $form_state) {
  $user = \Drupal::currentUser();
  $authorized = $user->hasPermission('create field_media_embeddable_code')
    || $user->hasPermission('edit field_media_embeddable_code');

  if (isset($form['container']['field_media_embeddable_code'])) {
    $form['container']['field_media_embeddable_code']['#access'] = $authorized;
  }
}

/**
 * Implements hook_entity_access().
 *
 * Restrict access to media entities that are used as field default values.
 */
function su_humsci_profile_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {

  // Only lock down the media entities since they are the default field values
  // that we care about.
  if (
    $entity->getEntityTypeId() != 'media' ||
    !in_array($operation, ['update', 'delete'])
  ) {
    return AccessResult::neutral();
  }

  $configs = \Drupal::configFactory()->listAll('field.field.');
  foreach ($configs as $config_name) {
    $config = \Drupal::config($config_name);
    // Check for the fields we are interested in.
    if (
      $config->get('field_type') == 'entity_reference' &&
      $config->get('settings.handler') == 'default:media' &&
      !empty($config->get('default_value'))
    ) {
      $default_value = $config->get('default_value');
      // The field default value matches the current media entity.
      if (!empty($default_value[0]['target_uuid']) && $entity->uuid() == $default_value[0]['target_uuid']) {
        return AccessResult::forbiddenIf(!$account->hasPermission('edit field default images'), 'The entity is set as a default field value.');
      }
    }
  }

  return AccessResult::neutral();
}

/**
 * Implements hook_preprocess_pattern_NAME().
 */
function su_humsci_profile_preprocess_pattern_alert(&$variables) {
  $entity_type = $variables['context']->getProperty('entity_type');
  $bundle = $variables['context']->getProperty('bundle');
  $entity = $variables['context']->getProperty('entity');

  // Global Messages!
  if ($entity_type == "config_pages" && $bundle == "stanford_global_message") {

    // Validate that the entity has the field we need so we don't 500 the site.
    if (!$entity->hasField('su_global_msg_type')) {
      \Drupal::logger('stanford_profile_helper')->error(t("Global Messages Config Block is missing the field su_global_msg_type"));
      return;
    }

    $color = $entity->get('su_global_msg_type')->getString();
    $variables['attributes']->addClass("su-alert--" . $color);
    $dark_bgs = ['error', 'info', 'success'];
    if (in_array($color, $dark_bgs)) {
      $variables['attributes']->addClass("su-alert--text-light");
    }
  }

}
