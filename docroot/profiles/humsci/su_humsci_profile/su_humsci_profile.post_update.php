<?php

/**
 * @file
 * su_humsci_profile.post_update.php
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\RoleInterface;

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
    'su_humsci_profile_post_update_8216' => '8.x-2.21',
  ];
}

/**
 * Set permissions for the new field on the accordions.
 */
function su_humsci_profile_post_update_8222() {
  $field_storage = [
    'uuid' => 'bd63d454-09fe-4bc2-b76b-738282b546d7',
    'field_name' => 'field_hs_accordion_views',
    'entity_type' => 'paragraph',
    'type' => 'viewfield',
    'third_party_settings' => [
      'field_permissions' => ['permission_type' => 'custom'],
    ],
  ];
  FieldStorageConfig::create($field_storage)->save();
  $field = [
    'uuid' => '69d6f231-0fd1-4ba3-b1ee-cc2618d45358',
    'field_name' => 'field_hs_accordion_views',
    'entity_type' => 'paragraph',
    'bundle' => 'hs_accordion',
  ];
  FieldConfig::create($field)->save();

  $permissions = [
    'view field_hs_accordion_views',
    'view own field_hs_accordion_views',
  ];
  user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, $permissions);
  user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, $permissions);
}

/**
 * Set the default image for profile content that doesn't have any images.
 */
function su_humsci_profile_post_update_8230() {
  /** @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages */
  $config_pages = \Drupal::service('config_pages.loader');
  $default_mid = $config_pages->getValue('field_default_values', 'field_people_image', 0, 'target_id');
  if (!$default_mid) {
    return;
  }
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $logger = \Drupal::logger('humsci_profile');

  $fields = ['field_hs_person_image', 'field_hs_person_square_img'];
  foreach ($fields as $image_field) {
    $nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_person')
      ->condition($image_field, NULL, 'IS NULL')
      ->execute();

    foreach ($node_storage->loadMultiple($nids) as $node) {
      $node->set($image_field, $default_mid)->save();
      $logger->info('Added default image to %field field on the profile %name', [
        '%field' => $image_field,
        '%name' => $node->label(),
      ]);
    }
  }
}
