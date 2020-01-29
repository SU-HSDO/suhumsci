<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\block\Entity\Block;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;

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
