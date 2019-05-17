<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\block\Entity\Block;
use Drupal\node\Entity\Node;

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
 * Hide block labels on overridden layouts.
 */
function su_humsci_profile_post_update_core_87_fix() {
  $ids = \Drupal::database()
    ->select('node__layout_builder__layout', 'l')
    ->fields('l', ['entity_id'])
    ->distinct()
    ->execute()
    ->fetchCol();
  foreach ($ids as $entity_id) {
    $node = Node::load($entity_id);
    $changed = FALSE;
    if (!$node->hasField('layout_builder__layout')) {
      continue;
    }

    $layout = $node->get('layout_builder__layout')->getValue();
    foreach ($layout as $delta => &$item) {
      /** @var \Drupal\layout_builder\Section $section */
      $section = $item['section'];
      /** @param \Drupal\layout_builder\SectionComponent $component */
      foreach ($section->getComponents() as $uuid => $component) {
        $config = $component->get('configuration');
        if (
          isset($config['provider']) &&
          $config['provider'] == 'views' &&
          $config['label'] == '' &&
          $config['views_label'] == ''
        ) {
          $config['label_display'] = 0;
          $component->setConfiguration($config);
          $changed = TRUE;
        }
      }
    }
    if ($changed) {
      $node->set('layout_builder__layout', $layout);
      $node->save();
    }
  }
}
