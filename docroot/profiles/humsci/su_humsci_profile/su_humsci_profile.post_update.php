<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Implements hook_removed_post_updates().
 */
function su_humsci_profile_removed_post_updates() {
  return [
    'su_humsci_profile_post_update_8200' => '8.x-2.18',
    'su_humsci_profile_post_update_8201' => '8.x-2.18',
    'su_humsci_profile_post_update_8202' => '8.x-2.18',
    'su_humsci_profile_post_update_8203' => '8.x-2.18',
    'su_humsci_profile_post_update_8204' => '8.x-2.18',
    'su_humsci_profile_post_update_8211' => '8.x-2.18',
    'su_humsci_profile_post_update_8212' => '8.x-2.18',
    'su_humsci_profile_post_update_8213' => '8.x-2.18',
    'su_humsci_profile_post_update_8214' => '8.x-2.18',
    'su_humsci_profile_post_update_8215' => '8.x-2.18',
    'su_humsci_profile_post_update_8_0_1' => '8.x-2.18',
    'su_humsci_profile_post_update_8_0_2' => '8.x-2.18',
    'su_humsci_profile_post_update_8_0_3' => '8.x-2.18',
    'su_humsci_profile_post_update_8_0_4' => '8.x-2.18',
    'su_humsci_profile_post_update_8_1_0' => '8.x-2.18',
    'su_humsci_profile_post_update_8_1_1' => '8.x-2.18',
  ];
}

/**
 * Convert event date field to smart date field.
 */
function su_humsci_profile_post_update_8216() {
  /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
  $form_display = EntityFormDisplay::load('node.hs_event.default');
  $form_field = $form_display->getComponent('field_hs_event_date');

  $view_config_names = \Drupal::configFactory()->listAll('views.view');
  $display_config_names = \Drupal::configFactory()
    ->listAll('core.entity_view_display.node.hs_event.');
  $views = [];
  $displays = [];
  foreach ($view_config_names as $config_name) {
    $views[$config_name] = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();
  };
  foreach ($display_config_names as $config_name) {
    $displays[$config_name] = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();
  };

  \Drupal::service('module_installer')->install(['smart_date']);
  drupal_flush_all_caches();
  $db = \Drupal::database();

  $tables = ['node__field_hs_event_date', 'node_revision__field_hs_event_date'];
  $table_data = [];
  foreach ($tables as $table) {
    $query = $db->select($table, 't')->fields('t')->execute();

    while ($row = $query->fetchAssoc()) {
      $row['field_hs_event_date_value'] = ((int) (strtotime($row['field_hs_event_date_value']) / 100)) * 100;
      $row['field_hs_event_date_end_value'] = ((int) (strtotime($row['field_hs_event_date_end_value']) / 100)) * 100;
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

  array_walk($views, '_suhumsci_profile_post_update_fix_view');
  array_walk($displays, '_suhumsci_profile_post_update_fix_node_display');
  $form_display = EntityFormDisplay::load('node.hs_event.default');
  $form_display->setComponent('field_hs_event_date', ['weight' => $form_field['weight']])
    ->save();
}

/**
 * Modify the field config data to work with the new smart date field.
 *
 * @param array $old_view
 *   Raw data from the view pre-update.
 */
function _suhumsci_profile_post_update_fix_view(array $old_view) {
  $fixed_data = $old_view;
  $view_changed = FALSE;
  foreach ($fixed_data['display'] as &$display) {
    if (empty($display['display_options']['fields'])) {
      continue;
    }

    foreach ($display['display_options']['fields'] as &$field) {
      if ($field['field'] != 'field_hs_event_date') {
        continue;
      }
      $view_changed = TRUE;

      switch ($field['type']) {
        case 'datetime_hs':
          $format = $field['settings']['date_format'];
          $column = $field['settings']['display'];
          $field['field'] = 'field_hs_event_date_' . ($column == 'start_date' ? 'value' : 'end_value');
          $field['date_format'] = 'custom';
          $field['custom_date_format'] = $format;
          $field['plugin_id'] = 'date';
          unset($field['settings']);
          break;

        case 'daterange_custom':
          $field['type'] = 'smartdate_custom';
          $field['settings'] = [
            'date_format' => $field['settings']['date_format'],
            'separator' => $field['settings']['separator'],
          ];
          break;

        case 'daterange_default':
          $field['settings']['format_type'] = 'default';
          break;
      }
    }
  }

  if (!$view_changed) {
    return;
  }
  \Drupal::logger('su_humsci_profile')
    ->info(t('Updated view: @view_name'), ['@view_name' => $old_view['label']]);
  \Drupal::messenger()
    ->addStatus(t('Updated view: @view_name', ['@view_name' => $old_view['label']]));

  \Drupal::configFactory()
    ->getEditable('views.view.' . $old_view['id'])
    ->setData($fixed_data)
    ->save();
}

/**
 * Adjust the display config array to set up the new smart date field.
 *
 * @param array $display
 *   Raw config data.
 */
function _suhumsci_profile_post_update_fix_node_display(array $display) {
  $display_changed = FALSE;

  if (isset($display['content']['field_hs_event_date'])) {
    $display_changed = TRUE;
    switch ($display['content']['field_hs_event_date']['type']) {
      case 'datetime_hs':
        $display['content']['field_hs_event_date'] = [
          'type' => 'smartdatetime_hs',
          'weight' => $display['content']['field_hs_event_date']['weight'],
          'region' => $display['content']['field_hs_event_date']['region'],
          'label' => $display['content']['field_hs_event_date']['label'],
          'third_party_settings' => [],
          'settings' => [
            'display' => $display['content']['field_hs_event_date']['settings']['display'] == 'start_date' ? 'start' : 'end',
            'date_format' => $display['content']['field_hs_event_date']['settings']['date_format'],
            'time_format' => '',
          ],
        ];
        break;
    }
  }

  if (!empty($display['third_party_settings']['layout_builder']['sections'])) {
    foreach ($display['third_party_settings']['layout_builder']['sections'] as &$section) {
      if (!empty($section['components'])) {
        foreach ($section['components'] as &$component) {
          if ($component['configuration']['id'] == 'field_block:node:hs_event:field_hs_event_date') {
            $display_changed = TRUE;
            switch ($component['configuration']['formatter']['type']) {
              case 'datetime_hs':
                $component['configuration']['formatter']['type'] = 'smartdatetime_hs';
                $component['configuration']['formatter']['settings'] = [
                  'display' => $component['configuration']['formatter']['settings']['display'] == 'start_date' ? 'start' : 'end',
                  'date_format' => $component['configuration']['formatter']['settings']['date_format'],
                  'time_format' => '',
                ];
                break;
            }
          }
        }
      }
    }
  }

  if ($display_changed) {
    \Drupal::configFactory()
      ->getEditable('core.entity_view_display.node.hs_event.' . $display['mode'])
      ->setData($display)
      ->save();
  }
}
