<?php

/**
 * @file
 * hs_admin.deploy.php
 *
 * Deploy hooks run after config import, making them suitable for operations
 * that depend on configuration existing first (e.g. new content types,
 * vocabularies, or permissions tied to them).
 *
 * hook_deploy_NAME() allows arbitrary machine names for NAME, with execution
 * order determined alphanumerically. By convention in this project, we use
 * purely numerical suffixes (e.g. _10001) rather than descriptive names. This
 * is a deliberate standard to mirror hook_update_N() conventions, keep
 * execution order explicit and predictable, and avoid the ambiguity of relying
 * on alphabetical sorting of arbitrary strings.
 *
 * @see https://github.com/drush-ops/drush/blob/-/drush.api.php
 */

use Drupal\user\RoleInterface;

/**
 * Add Training shortcuts to their respective dropdown menus.
 */
function hs_admin_deploy_10001(): string {
  $shortcut_storage = \Drupal::entityTypeManager()->getStorage('shortcut');

  // Use the same UUIDs as the shortcuts in the humsci_default_content module so
  // existing sites and fresh installs share a single, stable set of entities.
  // Parent UUIDs reference the default "Add content" and "Manage content"
  // shortcuts shipped by humsci_default_content.
  $new_shortcuts = [
    '44bd1036-9ff4-422c-bd97-1264215d6e9e' => [
      'title' => 'Add Training',
      'uri' => 'internal:/node/add/hs_training',
      'weight' => 8,
      'parent' => '3c769f63-f502-4f76-b4dd-85b7a584cd5b',
    ],
    'c8b3c7b6-0bac-4b5a-92cd-8cbe24fa7a02' => [
      'title' => 'Manage Training',
      'uri' => 'internal:/admin/content/manage/training',
      'weight' => 21,
      'parent' => '5744f2de-74a1-448f-aac1-61983a136cdb',
    ],
  ];

  $created = [];
  foreach ($new_shortcuts as $uuid => $config) {
    // Skip if a shortcut with this UUID already exists (e.g. provided by
    // humsci_default_content during install).
    if (!empty($shortcut_storage->loadByProperties(['uuid' => $uuid]))) {
      continue;
    }
    $values = [
      'uuid' => $uuid,
      'shortcut_set' => 'default',
      'title' => $config['title'],
      'link' => ['uri' => $config['uri']],
      'weight' => $config['weight'],
      'parent' => $config['parent'],
      'depth' => 1,
    ];
    $shortcut_storage->create($values)->save();
    $created[] = $config['title'];
  }

  return empty($created)
    ? 'Training shortcuts already exist, no changes made.'
    : 'Created shortcuts: ' . implode(', ', $created);
}

/**
 * Import hs_training display configs missing from active storage.
 */
function hs_admin_deploy_10002(): string {
  $sync = \Drupal::service('config.storage.sync');
  $active = \Drupal::service('config.storage');

  $configs = [
    'core.entity_form_display.node.hs_training.default',
    'core.entity_view_display.node.hs_training.default',
    'core.entity_view_display.node.hs_training.teaser',
  ];

  $imported = [];
  foreach ($configs as $name) {
    if (!$active->exists($name)) {
      $data = $sync->read($name);
      if ($data) {
        $active->write($name, $data);
        $imported[] = $name;
      }
    }
  }

  return empty($imported)
    ? 'hs_training display configs already present, no changes made.'
    : 'Imported configs: ' . implode(', ', $imported);
}

/**
 * Ensure training display exists in the hs_manage_content view.
 */
function hs_admin_deploy_10003(): string {
  $sync = \Drupal::service('config.storage.sync');
  $active = \Drupal::service('config.storage');

  $active_config = $active->read('views.view.hs_manage_content');
  if (empty($active_config)) {
    return 'hs_manage_content view not found in active storage.';
  }

  if (isset($active_config['display']['training'])) {
    return 'Training display already present in hs_manage_content view.';
  }

  $sync_config = $sync->read('views.view.hs_manage_content');
  if (empty($sync_config) || !isset($sync_config['display']['training'])) {
    return 'Training display not found in sync config.';
  }

  $active_config['display']['training'] = $sync_config['display']['training'];

  if (!in_array('node.type.hs_training', $active_config['dependencies']['config'] ?? [])) {
    $active_config['dependencies']['config'][] = 'node.type.hs_training';
  }

  $active->write('views.view.hs_manage_content', $active_config);
  return 'Added training display to hs_manage_content view.';
}

/**
 * Grant Training content and taxonomy permissions to roles.
 *
 * Role permissions are excluded from config import/export by config_ignore
 * (`user.role.*:permissions`), so the permission additions in the role config
 * files do not take effect on existing sites. This deploy hook runs after
 * config import, ensuring hs_training and its vocabularies exist first.
 */
function hs_admin_deploy_10004(): string {
  $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');

  $role_permissions = [
    'author' => [
      'edit own hs_training content',
    ],
    'contributor' => [
      'create hs_training content',
      'edit any hs_training content',
      'edit own hs_training content',
      'revert hs_training revisions',
      'view hs_training revisions',
    ],
    'preparer' => [
      'create hs_training content',
      'edit own hs_training content',
      'view any unpublished hs_training content',
    ],
    'reviewer' => [
      'view any unpublished hs_training content',
    ],
    'site_manager' => [
      'create hs_training content',
      'create terms in hs_training_audience',
      'create terms in hs_training_name',
      'create terms in hs_training_product',
      'create terms in hs_training_provider',
      'create terms in hs_training_unit',
      'delete any hs_training content',
      'delete own hs_training content',
      'delete terms in hs_training_audience',
      'delete terms in hs_training_name',
      'delete terms in hs_training_product',
      'delete terms in hs_training_provider',
      'delete terms in hs_training_unit',
      'edit any hs_training content',
      'edit own hs_training content',
      'edit terms in hs_training_audience',
      'edit terms in hs_training_name',
      'edit terms in hs_training_product',
      'edit terms in hs_training_provider',
      'edit terms in hs_training_unit',
      'revert hs_training revisions',
      'view hs_training revisions',
    ],
  ];

  $granted = [];
  foreach ($role_permissions as $role_id => $permissions) {
    $role = $role_storage->load($role_id);
    if (!$role instanceof RoleInterface) {
      continue;
    }
    $existing = $role->getPermissions();
    $to_grant = array_diff($permissions, $existing);
    if (!empty($to_grant)) {
      foreach ($to_grant as $perm) {
        $role->grantPermission($perm);
      }
      $role->save();
      $granted[$role_id] = count($to_grant);
    }
  }

  if (empty($granted)) {
    return 'No Training permissions needed granting; roles already up to date.';
  }

  $summary = [];
  foreach ($granted as $role_id => $count) {
    $summary[] = "$role_id (+$count)";
  }
  return 'Granted Training permissions to: ' . implode(', ', $summary) . '.';
}

/**
 * Re-import updated hs_training default display config.
 *
 * config_ignore prevents core.entity_view_display.node.hs_* from being
 * imported by drush config:import, so this hook imitates that process manually.
 */
function hs_admin_deploy_10005(): string {
  $sync = \Drupal::service('config.storage.sync');
  $name = 'core.entity_view_display.node.hs_training.default';
  $data = $sync->read($name);
  if (!$data) {
    return "Config $name not found in sync storage, no changes made.";
  }

  \Drupal::configFactory()->getEditable($name)->setData($data)->save();
  return "Imported updated $name from sync to active storage.";
}
