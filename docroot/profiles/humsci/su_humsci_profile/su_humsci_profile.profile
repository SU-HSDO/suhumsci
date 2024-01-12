<?php

/**
 * @file
 * su_humsci_profile.profile
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\menu_position\Entity\MenuPositionRule;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\su_humsci_profile\HumsciCleanup;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Implements hook_ENTITY_TYPE_insert().
 */
function su_humsci_profile_user_role_insert(RoleInterface $role) {
  _su_humsci_profile_update_samlauth();
}

/**
 * Implements hook_ENTITY_TYPE_delete().
 */
function su_humsci_profile_user_role_delete(RoleInterface $entity) {
  _su_humsci_profile_update_samlauth();
}

/**
 * Update samlauth user role mapping to always align with the current roles.
 */
function _su_humsci_profile_update_samlauth() {
  $samlauth_roles = [];
  foreach (array_keys(user_role_names(TRUE)) as $role_id) {
    $samlauth_roles[$role_id] = $role_id;
  }
  unset($samlauth_roles[UserInterface::AUTHENTICATED_ROLE]);
  asort($samlauth_roles);
  \Drupal::configFactory()
    ->getEditable('samlauth.authentication')
    ->set('map_users_roles', $samlauth_roles)
    ->save();
}

/**
 * Implements hook_help().
 */
function su_humsci_profile_help($route_name, RouteMatchInterface $route_match) {
  $path = $route_match->getRouteObject()->getPath();
  switch ($path) {
    case '/admin/users':
      return '<p>Need help giving someone permission to edit the site? Consult the <a href="https://hsweb.slite.page/p/Qlk4KqR8GW9qsn/Manage-User-Permissions">Manage User Permission section of the User Guide</a>.</p>';
  }
}

/**
 * Implements hook_library_info_alter().
 */
function su_humsci_profile_library_info_alter(&$libraries, $extension) {
  // Disable confirm leave library during testing.
  if ($extension == 'confirm_leave' && getenv('CI')) {
    unset($libraries['confirm-leave']);
  }
}

/**
 * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
 */
function su_humsci_profile_field_widget_complete_paragraphs_form_alter(&$field_widget_complete_form, FormStateInterface $form_state, $context) {
  $max_delta = $field_widget_complete_form['widget']['#max_delta'] ?? -1;
  for ($delta = 0; $delta <= $max_delta; $delta++) {
    if (isset($field_widget_complete_form['widget'][$delta]['top']['actions']['dropdown_actions']['duplicate_button'])) {
      $duplicate_button = &$field_widget_complete_form['widget'][$delta]['top']['actions']['dropdown_actions']['duplicate_button'];
      $duplicate_button['#ajax']['callback'] = 'su_humsci_profile_paragraphs_duplicate_callback';
    }
  }
}

/**
 * Ajax callback for duplicate button on paragraphs.
 *
 * @param array $form
 *   Complete form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Ajax form state.
 *
 * @return array
 *   Modified ajax element.
 */
function su_humsci_profile_paragraphs_duplicate_callback(array $form, FormStateInterface $form_state) {
  $element = ParagraphsWidget::itemAjax($form, $form_state);
  $added_delta = $element['#max_delta'];
  $element[$added_delta]['#attributes']['class'][] = 'hs-duplicated';
  $element['#attached']['library'][] = 'su_humsci_profile/paragraphs';
  return $element;
}

/**
 * Implements hook_entity_type_alter().
 */
function su_humsci_profile_entity_type_alter(array &$entity_types) {
  if (isset($entity_types['menu_link_content'])) {
    $entity_types['menu_link_content']->addConstraint('menu_link_item_url_constraint');
  }
}

/**
 * Implements hook_form_alter().
 */
function su_humsci_profile_form_user_login_form_alter(&$form, FormStateInterface $form_state) {
  if (isset($form['manual']['#open'])) {
    $manual_label = \Drupal::state()->get('stanford_ssp.manual_label', FALSE);
    if ($manual_label) {
      $form['manual']['#open'] = TRUE;
      $form['manual']['#title'] = $manual_label;
    }
  }
}

/**
 * Implements hook_pathauto_pattern_alter().
 */
function su_humsci_profile_pathauto_pattern_alter(PathautoPatternInterface $pattern, array $context) {
  // Only adjust node path aliases.
  if ($context['module'] != 'node' || !isset($context['data']['node'])) {
    return;
  }
  /** @var \Drupal\node\NodeInterface $node */
  $node = $context['data']['node'];
  // If a node doesn't allow menu settings, we exit.
  if (!isset($node->menu)) {
    return;
  }
  $parent = explode(':', $node->menu['menu_parent']);

  // Make sure the parent menu item is a link content entity. The common form
  // of the parent is `[menu_name]:[type]:[uuid]`.
  if (
    count($parent) >= 3 &&
    $parent[0] == 'main' &&
    $parent[1] == 'menu_link_content'
  ) {
    $parent_menu_item = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['uuid' => $parent[2]]);
    $parent_menu_item = reset($parent_menu_item);
    $link_uri = $parent_menu_item->get('link')
      ->get(0)
      ->get('uri')
      ->getString();

    // If the parent menu item is a no-link, change the path alias pattern.
    if ($link_uri == 'route:<nolink>') {
      $search = '[node:menu-link:parent:url:relative]';
      $replacement = '[node:menu-link:parents:join-path]';
      $pattern->setPattern(str_replace($search, $replacement, $pattern->getPattern()));
    }
  }
}

/**
 * Implements hook_entity_presave().
 */
function su_humsci_profile_entity_presave(EntityInterface $entity) {
  if ($entity instanceof ConfigPagesInterface) {
    Cache::invalidateTags(['migration_plugins']);
  }
}

/**
 * Implements hook_metatags_alter().
 */
function su_humsci_profile_metatags_alter(array &$metatags, array &$context) {
  if ($context['entity'] instanceof NodeInterface && $context['entity']->bundle() == 'hs_basic_page') {
    if ($context['entity']->hasField('field_hs_page_hero') && $context['entity']->get('field_hs_page_hero')->count()) {

      /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $field_item */
      $field_item = $context['entity']->get('field_hs_page_hero')->get(0);
      $paragraph_id = $field_item->get('target_id')->getString();

      $paragraph = \Drupal::entityTypeManager()
        ->getStorage('paragraph')
        ->load($paragraph_id);

      if (!$paragraph) {
        return;
      }

      switch ($paragraph->bundle()) {
        case 'hs_gradient_hero_slider':
          $metatags = su_humsci_profile_preg_replace("/(.*)field_hs_banner_image(.*)/", '$1field_hs_gradient_hero_slides:entity:field_hs_gradient_hero_image$2', $metatags);
          break;

        case 'hs_carousel':
          $metatags = su_humsci_profile_preg_replace("/(.*)field_hs_banner_image(.*)/", '$1field_hs_carousel_slides:entity:field_hs_hero_image$2', $metatags);
          break;

        case 'hs_hero_image':
          $metatags = su_humsci_profile_preg_replace("/(.*)field_hs_banner_image(.*)/", '$1field_hs_hero_image$2', $metatags);
          break;

        case 'hs_sptlght_slder':
          $metatags = su_humsci_profile_preg_replace("/(.*)field_hs_banner_image(.*)/", '$1field_hs_sptlght_sldes:entity:field_hs_spotlight_image$2', $metatags);
          break;

      }
    }
  }
}

/**
 * Similar to `preg_replace`, but only on string values.
 *
 * @param mixed $search
 *   Search string or array.
 * @param mixed $replace
 *   Replace string or array.
 * @param mixed $subject
 *   Subject string or array.
 *
 * @return mixed
 *   Modified string or array.
 */
function su_humsci_profile_preg_replace($search, $replace, $subject) {
  foreach ($subject as &$item) {
    if (is_string($item)) {
      $item = preg_replace($search, $replace, $item);
    }
  }
  return $subject;
}

/**
 * Implements hook_entity_delete().
 */
function su_humsci_profile_entity_delete(EntityInterface $entity) {
  su_humsci_profile_entity_presave($entity);
}

/**
 * Implements hook_preprocess_HOOK().
 */
function su_humsci_profile_preprocess_image_formatter(&$variables) {
  if (isset($variables['url'])) {
    // Disable screen readers from seeing the link on the image since there
    // should be another link with text nearby.
    $variables['url']->mergeOptions([
      'attributes' => [
        'tabindex' => -1,
        'aria-hidden' => 'true',
      ],
    ]);
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function su_humsci_profile_preprocess_responsive_image_formatter(&$variables) {
  if (isset($variables['url'])) {
    // Disable screen readers from seeing the link on the image since there
    // should be another link with text nearby.
    $variables['url']->mergeOptions([
      'attributes' => [
        'tabindex' => -1,
        'aria-hidden' => 'true',
      ],
    ]);
  }
}

/**
 * Implements hook_ui_patterns_info_alter().
 */
function su_humsci_profile_ui_patterns_info_alter(array &$definitions) {
  $theme = \Drupal::config('system.theme')->get('default');
  $profile_settings = \Drupal::config('su_humsci_profile.settings');
  $newer_themes = $profile_settings->get('new_themes') ?: [];
  $keep_libraries = [
    'masonry_item',
    'masonry',
  ];

  if (in_array($theme, $newer_themes)) {
    /** @var \Drupal\ui_patterns\Definition\PatternDefinition $definition */
    foreach ($definitions as $definition) {
      // On newer themes, unset the libraries for all patterns except a few
      // specific ones.
      if (!in_array($definition->id(), $keep_libraries)) {
        $definition->setLibraries([]);
      }
    }
  }
}

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
  $entity_types = [
    'node' => 'Content',
    'media' => 'Media Item',
    'paragraph' => 'Paragraph',
  ];
  if (isset($entity_types[$group])) {
    foreach ($links as &$link) {
      $link['title'] .= " {$entity_types[$group]}";
    }
  }
  if (
    !in_array($group, ['media', 'block_content']) &&
    !\Drupal::currentUser()->hasPermission('view all contextual links')
  ) {
    $links = [];
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
function su_humsci_profile_node_insert(NodeInterface $node) {
  // Clear menu links cache if the node has a menu link data.
  if (
    $node->hasField('field_menulink') &&
    !$node->get('field_menulink')->isEmpty()
  ) {
    _su_humsci_clear_menu_cache_tags();
  }
}

/**
 * Implements hook_ENTITY_TYPE_update().
 */
function su_humsci_profile_node_update(NodeInterface $node) {
  /** @var \Drupal\node\NodeInterface $original_node */
  $original_node = $node->original;
  // Compare the original menu link with the new menu link data. If any
  // important parts changed, clear the menu links cache.
  if (
    $node->hasField('field_menulink') &&
    (!$node->get('field_menulink')->isEmpty() || !$original_node->get('field_menulink')->isEmpty())
  ) {

    $keys = ['title', 'description', 'weight', 'expanded', 'parent'];
    $changes = $node->get('field_menulink')->getValue();
    $original = $original_node->get('field_menulink')->getValue();

    foreach ($keys as $key) {
      $change_value = $changes[0][$key] ?? NULL;
      $original_value = $original[0][$key] ?? NULL;

      if ($change_value != $original_value) {
        _su_humsci_clear_menu_cache_tags();
        return;
      }
    }
  }
}

/**
 * Implements hook_ENTITY_TYPE_delete().
 */
function su_humsci_profile_node_delete(NodeInterface $node) {
  // If a node has menu link data, delete the menu link.
  if (
    $node->hasField('field_menulink') &&
    !$node->get('field_menulink')->isEmpty()
  ) {
    \Drupal::database()->delete('menu_tree')
      ->condition('id', 'menu_link_field:%', 'LIKE')
      ->condition('route_param_key', 'node=' . $node->id())
      ->execute();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    _su_humsci_clear_menu_cache_tags();
  }
}
/**
 * Implements hook_ENTITY_TYPE_insert().
 */
function su_humsci_profile_menu_link_content_presave(MenuLinkContentInterface $entity) {
  // For new menu link items created on a node form (normally), set the expanded
  // attribute so all mcenu items are expanded by default.
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
  _su_humsci_clear_menu_cache_tags();
}

/**
 * Clear the menu link cache tags.
 */
function _su_humsci_clear_menu_cache_tags() {
  Cache::invalidateTags(['su_humsci_profile:menu_links']);
}

/**
 * Implements hook_entity_operation_alter().
 */
function su_humsci_profile_entity_operation_alter(array &$operations, EntityInterface $entity) {
  $role_delegation = \Drupal::moduleHandler()->moduleExists('role_delegation');
  if ($entity instanceof UserInterface && $role_delegation) {
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

  $good_plugins = \Drupal::config('su_humsci_profile.settings')
    ->get('allowed.condition_plugins');
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
  if (empty($variables['menu_name']) || $variables['menu_name'] != 'shortcut_menu') {
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
      \Drupal::logger('su_humsci_profile')
        ->error(t("Global Messages Config Block is missing the field su_global_msg_type"));
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

/**
 * Implements hook_node_access().
 */
function su_humsci_profile_node_access(EntityInterface $node, $op, AccountInterface $account) {
  if ($op == 'delete') {
    $site_config = \Drupal::config('system.site');
    $node_urls = [$node->toUrl()->toString(), "/node/{$node->id()}"];

    // If the node is configured to be the home page, 404, or 403, prevent the
    // user from deleting. Unfortunately this only works for roles without the
    // "Bypass content access control" permission.
    if (array_intersect($node_urls, $site_config->get('page'))) {
      return AccessResult::forbidden();
    }
  }
  return AccessResult::neutral();
}

/**
 * Implements hook_ENTITY_TYPE_access().
 */
function su_humsci_profile_user_access(UserInterface $entity, $operation, AccountInterface $account) {
  return _su_humsci_profile_allowed_to_grant_role($account);
}

/**
 * Implements hook_entity_field_access().
 */
function su_humsci_profile_entity_field_access($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
  if ($operation == 'view' && $field_definition->getTargetEntityTypeId() == 'user') {
    return AccessResult::allowedIfHasPermission($account, 'view user list');
  }
  if (
    $operation == 'edit' &&
    $field_definition->getName() == 'roles' &&
    $items->getEntity() instanceof UserInterface
  ) {
    return _su_humsci_profile_allowed_to_grant_role($account);
  }
  if (
    $field_definition->getName() == 'status' &&
    $field_definition->getTargetEntityTypeId() == 'node' &&
    $items &&
    $items->getEntity()->id()
  ) {
    // Prevent unpublishing the home, 404 and 403 pages.
    return su_humsci_profile_node_access($items->getEntity(), 'delete', $account);
  }
  return AccessResult::neutral();
}

/**
 * Check if the current user has permission to grant the role being triggered.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Current account.
 *
 * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultNeutral|\Drupal\Core\Access\AccessResultReasonInterface
 *   Result of the access check.
 */
function _su_humsci_profile_allowed_to_grant_role(AccountInterface $account) {
  $action = \Drupal::requestStack()
    ->getCurrentRequest()->request->get('action', '');
  if (preg_match('/user_.*_action\.(.*)/', $action, $matches)) {
    $role_name = $matches[1];
    return AccessResult::allowedIfHasPermission($account, "assign $role_name role");
  }
  return AccessResult::neutral();
}

/**
 * Implements hook_form_alter().
 */
function su_humsci_profile_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (preg_match('/^node.*edit_form$/', $form_id)) {
    $node = $form_state->getBuildInfo()['callback_object']->getEntity();
    $access = su_humsci_profile_node_access($node, 'delete', \Drupal::currentUser());
    $form['status']['#access'] = !$access->isForbidden();
  }
}

/**
 * Implements hook_ENTITY_TYPE_delete().
 */
function su_humsci_profile_menu_link_content_delete(MenuLinkContentInterface $entity) {
  _su_humsci_clear_menu_cache_tags();
}

/**
 * Implements hook_ENTITY_TYPE_update().
 */
function su_humsci_profile_menu_link_content_update(MenuLinkContentInterface $entity) {
  $original = [
    $entity->original->get('title')->getValue(),
    $entity->original->get('description')->getValue(),
    $entity->original->get('link')->getValue(),
    $entity->original->get('parent')->getValue(),
    $entity->original->get('weight')->getValue(),
    $entity->original->get('expanded')->getValue(),
  ];
  $updated = [
    $entity->get('title')->getValue(),
    $entity->get('description')->getValue(),
    $entity->get('link')->getValue(),
    $entity->get('parent')->getValue(),
    $entity->get('weight')->getValue(),
    $entity->get('expanded')->getValue(),
  ];
  if (md5(json_encode($original)) != md5(json_encode($updated))) {
    _su_humsci_clear_menu_cache_tags();
  }
}

/**
 * Implements hook_block_build_alter().
 */
function su_humsci_profile_block_build_alter(array &$build, BlockPluginInterface $block) {
  if ($block->getBaseId() == 'system_menu_block') {
    $build['#cache']['tags'][] = 'su_humsci_profile:menu_links';
    HumsciCleanup::removeCacheTags($build, [
      '^node:*',
      '^config:system.menu.*',
    ]);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function su_humsci_profile_form_user_register_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['simplesamlphp_auth_user_enable']['#default_value'] = (bool) \Drupal::state()->get('humsci_profile.user_saml_default', TRUE);
}

/**
 * Implements hook_preprocess_HOOK().
 */
function su_humsci_profile_preprocess_block__stanford_samlauth(&$variables) {
  $variables['content']['login']['#attributes']['class'] = [
    'text-align-right',
    'hs-secondary-button',
  ];
}
