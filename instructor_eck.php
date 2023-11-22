<?php

// Migration code for HSD8-1496 Instructor ECK migration.

// Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
$entity_type_manager = \Drupal::entityTypeManager();

// Get all fields referencing this ECK collection.
$fields = $entity_type_manager->getStorage('field_storage_config')
  ->loadByProperties([
    'type' => 'entity_reference',
    'settings' => ['target_type' => 'course_collections'],
  ]);

// Loop through fields and change target reference type.
foreach ($fields as $config_name => $field_storage_config) {
  foreach($field_storage_config->getBundles() as $bundle) {
    // Update the field config settings for each bundle.
    $field_config =  $entity_type_manager->getStorage('field_config')
      ->load('node.' . $bundle . '.' . $field_storage_config->getName());
    $handler_settings = $field_config->getSetting('handler_settings');
    $handler_settings['target_bundles'] = ['instructor' => 'instructor'];
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();
  }

  // Update the field storage settings.
  $field_storage_config->setSetting('target_type', 'hs_entity');
  $field_storage_config->save();
}

// Get all ECK instructor course collections.
$instructor_ecks = $entity_type_manager->getStorage('course_collections')
  ->loadByProperties(['type' => 'instructor']);
foreach($instructor_ecks as $eck_entity) {

  // Create the new hs entity using the values of the ECK.
  $hs_entity = $entity_type_manager->getStorage('hs_entity')
  ->create([
    'bundle' => 'instructor',
    'field_person' => $eck_entity->get('field_instructor_person')->getString(),
    'field_instructor_role' => $eck_entity->get('field_instructor_role')->getString(),
  ]);
  // $hs_entity->save();


  // Now I need to pull nodes by fields that reference this id, without
  // necessarily knowing the field names?
  $query = \Drupal::entityQuery('node')


  // $node = $entity_type_manager->getStorage('field_storage_config')
  //   ->loadByProperties(['type' => 'entity_reference',
  //     'settings' => ['target_type' => 'hs_entity'],
  //   ]);
  //   var_dump($node);
}



// $node = $entity_type_manager->getStorage('node')
// ->load(4);
// var_dump($node);
// $node->save();


// field_hs_course_section_instruc



// Update the field storage settings.
// $field_storage_config = FieldStorageConfig::loadByName('config_pages', 'field_news_rss');
// $field_storage_config->setSetting('target_type', 'hs_entity');
// $field_storage_config->save();

// Update the field config settings.
// $field_config =  \Drupal::entityTypeManager()->getStorage('field_config')
//   ->load('config_pages.news_rss.field_news_rss');
// $handler_settings = $field_config->getSetting('handler_settings');
// $handler_settings['target_bundles'] = ['news_rss' => 'news_rss'];
// $field_config->setSetting('handler_settings', $handler_settings);
// $field_config->save();
