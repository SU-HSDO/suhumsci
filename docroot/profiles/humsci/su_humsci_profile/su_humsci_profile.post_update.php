<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\block\Entity\Block;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\Entity\Role;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Outdated.
 */
function su_humsci_profile_post_update_8_0_1() {

}

/**
 * Outdated.
 */
function su_humsci_profile_post_update_8_0_2() {

}

/**
 * Outdated.
 */
function su_humsci_profile_post_update_8_0_3() {

}

/**
 * Delete masquerade blocks.
 */
function su_humsci_profile_post_update_8_0_4() {
  if ($block = Block::load('seven_masquerade')) {
    $block->delete();
  }
  if ($block = Block::load('su_humsci_admin_masquerade')) {
    $block->delete();
  }
}

/**
 * Fix layout builder block display.
 *
 * Views exposed filter blocks started showing the view title and we need to
 * hide them as configured.
 */
function su_humsci_profile_post_update_8_1_0() {
  $database = \Drupal::database();
  $tables = [
    'node__layout_builder__layout',
    'node_revision__layout_builder__layout',
  ];

  foreach ($tables as $table) {
    $query = $database->select($table, 'l')
      ->fields('l')
      ->execute();
    while ($row = $query->fetchAssoc()) {
      $changed_row = FALSE;
      /** @var \Drupal\layout_builder\Section $layout_section */
      $layout_section = unserialize($row['layout_builder__layout_section']);
      foreach ($layout_section->getComponents() as $component) {
        $config = $component->get('configuration');
        if (
          isset($config['provider']) &&
          $config['provider'] == 'views' &&
          $config['label'] == '' &&
          $config['views_label'] == ''
        ) {
          $config['label_display'] = 0;
          $component->setConfiguration($config);
          $changed_row = TRUE;
        }
      }

      if ($changed_row) {
        $database->update($table)
          ->fields(['layout_builder__layout_section' => serialize($layout_section)])
          ->condition('entity_id', $row['entity_id'])
          ->condition('revision_id', $row['revision_id'])
          ->condition('delta', $row['delta'])
          ->execute();
      }
    }
  }
}

/**
 * Adds carousel to the hero field on basic pages.
 */
function su_humsci_profile_post_update_8_1_1() {
  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = FieldConfig::load('node.hs_basic_page.field_hs_page_hero');
  $settings = $field->getSettings();
  $settings['handler_settings']['target_bundles']['hs_carousel'] = 'hs_carousel';
  $settings['handler_settings']['target_bundles_drag_drop']['hs_carousel'] = [
    'enabled' => TRUE,
    'weight' => 9,
  ];
  $field->set('settings', $settings);
  $field->save();
}

/**
 * Uninstall unwanted modules.
 */
function su_humsci_profile_post_update_8200() {
  /** @var \Drupal\filter\FilterFormatInterface $filter_format */
  foreach (FilterFormat::loadMultiple() as $filter_format) {
    $filters = $filter_format->get('filters');
    unset($filters['entity_embed']);
    $filter_format->set('filters', $filters);
    $filter_format->calculateDependencies();
    $filter_format->save();
  }

  /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->uninstall(['embed', 'entity_browser']);
}

/**
 * Add setting to content access to restrict access to private page.
 */
function su_humsci_profile_post_update_8201() {
  $config = \Drupal::configFactory()->getEditable('content_access.settings');
  $node_access = $config->get('content_access_node_type') ?: [];

  $settings = ['view' => ['authenticated'], 'per_node' => 1];
  $node_access['hs_private_page'] = serialize($settings);

  $config->set('content_access_node_type', $node_access);

  $new_perms = [
    'create hs_private_page content',
    'delete any hs_private_page content',
    'delete own hs_private_page content',
    'edit any hs_private_page content',
    'edit own hs_private_page content',
    'revert hs_private_page revisions',
    'view hs_private_page revisions',
  ];
  user_role_grant_permissions('site_manager', $new_perms);
  user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['use text format basic_html_without_media']);

  /** @var \Drupal\field\FieldConfigInterface $field */
  $field = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->load('node.hs_basic_page.field_hs_page_components');
  $settings = $field->getSettings();

  if ($settings['handler_settings']['negate']) {
    $settings['handler_settings']['target_bundles']['hs_private_files'] = 'hs_private_files';
    $settings['handler_settings']['target_bundles']['hs_priv_text_area'] = 'hs_priv_text_area';
    $settings['handler_settings']['target_bundles_drag_drop']['hs_private_files'] = ['enabled' => FALSE];
    $settings['handler_settings']['target_bundles_drag_drop']['hs_priv_text_area'] = ['enabled' => FALSE];;
    $field->set('settings', $settings);
    $field->calculateDependencies();
    $field->save();
  }
}

/**
 * Create the private page entity form and entity display configs.
 */
function su_humsci_profile_post_update_8202() {
  $configs = [
    'core.entity_form_display.node.hs_private_page.default',
    'core.entity_view_display.node.hs_private_page.default',
    'core.entity_view_display.node.hs_private_page.teaser',
  ];
  $config_factory = \Drupal::configFactory();
  foreach ($configs as $config) {
    $data = Yaml::decode(file_get_contents(DRUPAL_ROOT . "/../config/default/$config.yml"));
    $config_factory->getEditable($config)
      ->setData($data)
      ->save();
  }
}

/**
 * Copy the media module icon to the site's files directory.
 */
function su_humsci_profile_post_update_8203() {
  /** @var \Drupal\Core\File\FileSystem $file_system */
  $file_system = \Drupal::service('file_system');
  $source = DRUPAL_ROOT . '/' . drupal_get_path('module', 'media') . '/images/icons/generic.png';
  $destination = $file_system->realpath('public://media-icons/generic/generic.png');
  if (!file_exists($destination)) {
    $directory = 'public://media-icons/generic';
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $file_system->copy($source, "$directory/generic.png", FileSystemInterface::EXISTS_REPLACE);
  }
}

/**
 * Uninstall update module.
 */
function su_humsci_profile_post_update_8204() {
  \Drupal::service('module_installer')->uninstall(['update']);
}

/**
 * Modify the events importer database to use the new unique IDS.
 */
function su_humsci_profile_post_update_8211(&$sandbox) {
  $schema = \Drupal::database()->schema();

  \Drupal::configFactory()
    ->getEditable('migrate_plus.migration.hs_events_image_importer')
    ->delete();
  if ($schema->tableExists('migrate_map_hs_events_image_importer')) {
    $schema->dropTable('migrate_map_hs_events_image_importer');
  }

  if (!$schema->tableExists('migrate_map_hs_events_importer') ||
    ($schema->fieldExists('migrate_map_hs_events_importer', 'sourceid2') && $schema->fieldExists('migrate_map_hs_events_importer', 'sourceid3'))
  ) {
    return;
  }
  $field_info = [
    'type' => 'varchar',
    'length' => '255',
    'not null' => TRUE,
    'initial' => '[replace]',
  ];

  if (!$schema->fieldExists('migrate_map_hs_events_importer', 'sourceid2')) {
    $schema->addField('migrate_map_hs_events_importer', 'sourceid2', $field_info);
  }
  if (!$schema->fieldExists('migrate_map_hs_events_importer', 'sourceid3')) {
    $schema->addField('migrate_map_hs_events_importer', 'sourceid3', $field_info);
  }

  $event_urls = \Drupal::config('hs_events_importer.settings')->get('urls');
  if (empty($event_urls)) {
    return;
  }

  $database = \Drupal::database();
  $guzzle = \Drupal::httpClient();

  foreach ($event_urls as $url) {
    try {
      $response = $guzzle->request('GET', $url);
    }
    catch (GuzzleException | \Exception $e) {
      continue;
    }

    $body = (string) $response->getBody();
    $xml = simplexml_load_string($body);

    /** @var \SimpleXMLElement $event */
    foreach ($xml->xpath('//Event') as $event) {
      $guid = (string) $event->xpath('guid')[0];
      $event_id = (string) $event->xpath('eventID')[0];
      $start = (string) $event->xpath('isoEventDate')[0];
      $end = (string) $event->xpath('isoEventEndDate')[0];
      $source_id_values = [$event_id, $start, $end];
      $hash = hash('sha256', serialize(array_map('strval', $source_id_values)));;

      $database->update('migrate_map_hs_events_importer')
        ->condition('sourceid1', $guid)
        ->fields([
          'source_ids_hash' => $hash,
          'sourceid1' => $event_id,
          'sourceid2' => $start,
          'sourceid3' => $end,
        ])
        ->execute();
    }
  }
}

/**
 * Implements hook_post_update_NAME().
 */
function su_humsci_profile_post_update_8212(&$sandbox) {
  \Drupal::service('module_installer')->uninstall(['jira_rest']);
}

/**
 * Adjust media permissions.
 */
function su_humsci_profile_post_update_8213() {
  foreach (Role::loadMultiple() as $role) {
    user_role_revoke_permissions($role->id(), ['administer media']);
  }
  user_role_grant_permissions('site_manager', ['access media overview']);
  user_role_grant_permissions('contributor', ['access media overview']);
}

/**
 * Exclude private files and text area from basic page.
 */
function su_humsci_profile_post_update_8214() {
  $field_config = \Drupal::configFactory()
    ->getEditable('field.field.node.hs_basic_page.field_hs_page_components');
  $settings = 'settings.handler_settings.target_bundles_drag_drop';
  $negate = (bool) $field_config->get('settings.handler_settings.negate');
  $field_config->set("$settings.hs_private_files.enabled", $negate);
  $field_config->set("$settings.hs_priv_text_area.enabled", $negate);
  $field_config->save();
}

/**
 * Uninstall rules module.
 */
function su_humsci_profile_post_update_8215(&$sandbox) {
  \Drupal::service('module_installer')->uninstall(['rules']);
}

/**
 * Convert event date field to smart date field.
 */
function su_humsci_profile_post_update_8216() {
  \Drupal::service('module_installer')->install(['smart_date']);
  $db = \Drupal::database();

  $tables = ['node__field_hs_event_date', 'node_revision__field_hs_event_date'];
  $table_data = [];
  foreach ($tables as $table) {
    $query = $db->select($table, 't')->fields('t')->execute();

    while ($row = $query->fetchAssoc()) {
      $row['field_hs_event_date_value'] = strtotime($row['field_hs_event_date_value']) - 7 * 60 * 60;
      $row['field_hs_event_date_end_value'] = strtotime($row['field_hs_event_date_end_value']) - 7 * 60 * 60;
      $table_data[$table][] = $row;
    }
    $db->truncate($table)->execute();
  }
  FieldConfig::load('node.hs_event.field_hs_event_date')->delete();
  if ($field_storage = FieldStorageConfig::load('node.field_hs_event_date')) {
    $field_storage->delete();
  }

  // Add an untranslatable node reference field.
  FieldStorageConfig::create([
    'uuid' => 'e7c58e93-8004-4486-983e-f6c0522f4fde',
    'field_name' => 'field_hs_event_date',
    'type' => 'smartdate',
    'entity_type' => 'node',
  ])->save();
  FieldConfig::create([
    'uuid' => '9e106871-97fc-4960-ac4f-f24a78ca7da6',
    'field_name' => 'field_hs_event_date',
    'entity_type' => 'node',
    'bundle' => 'hs_event',
    'label' => 'Event Date',
  ])->save();

  $key_value = \Drupal::keyValue('entity.definitions.bundle_field_map')
    ->get('node');
  $key_value['field_hs_event_date']['type'] = 'smartdate';
  \Drupal::keyValue('entity.definitions.bundle_field_map')
    ->set('node', $key_value);

  foreach ($table_data as $table => $rows) {
    foreach ($rows as $row) {
      $db->insert($table)->fields($row)->execute();
    }
  }
}
