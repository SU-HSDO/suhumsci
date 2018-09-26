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
