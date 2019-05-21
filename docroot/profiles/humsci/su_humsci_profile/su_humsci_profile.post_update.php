<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\block\Entity\Block;

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
