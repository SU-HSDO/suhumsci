<?php

use \Drupal\field\Entity\FieldStorageConfig;
use \Drupal\field\Entity\FieldConfig;


// Testing the ECK deletions.
// First delete the news_rss bundle. Do I need to manually delete the field as
// well or will deleting the bundle auto delete the fields? It does through the
// UI, but I'm not sure about programatically.
$entity_type_manager = \Drupal::entityTypeManager();
// if ($entity_type_manager->hasDefinition('importers_type')) {
//   $news_rss_eck_bundle = $entity_type_manager->getStorage('importers_type')->load('news_rss');
//   $news_rss_eck_bundle->delete();
// }

// Second delete the Importer collections entity type from ECK.
if ($entity_type_manager->hasDefinition('eck_entity_type')) {
  $importers_collection = $entity_type_manager->getStorage('eck_entity_type')->load('importers');
  $importers_collection->delete();
}


// @todo Turn this into an updatedb hook.

// REVERT STORAGE SETTINGS BACK
// $field_storage = FieldStorageConfig::loadByName('config_pages', 'field_news_rss');
// $field_storage->setSetting('target_type', 'importers');
// $field_storage->save();

// $field_config =  \Drupal::entityTypeManager()->getStorage('field_config')
//   ->load('config_pages.news_rss.field_news_rss');
// $handler_settings = $field_config->getSetting('handler_settings');
// $handler_settings['target_bundles'] = ['news_rss' => 'news_rss'];
// $field_config->setSetting('handler_settings', $handler_settings);
// $field_config->save();


// SCRIPT STARTS BELOW
// $config_page_references = [];

// $entity_type_manager = \Drupal::entityTypeManager();
// if ($entity_type_manager->hasDefinition('importers')) {
//   // Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage.
//   $storage = $entity_type_manager->getStorage('importers');
//   $entities = $storage->loadByProperties(['type' => 'news_rss']);
//   // Drupal\eck\Entity\EckEntity $entity.
//   foreach ($entities as $entity) {
//     // Drupal\hs_entities\Entity\HsImporter $hs_importer.
//     $hs_importer = \Drupal::entityTypeManager()->getStorage('hs_importer')->create([
//       'bundle' => 'news_rss'
//     ]);
//     $hs_importer->set('field_url', $entity->get('field_url')->getValue());
//     $hs_importer->set('field_terms', $entity->get('field_terms')->getValue());
//     $hs_importer->save();

//     $config_pages = \Drupal::entityTypeManager()->getStorage('config_pages')->loadByProperties([
//       'type' => 'news_rss',
//       'field_news_rss' => $entity->id(),
//     ]);
//     // Drupal\config_pages\Entity\ConfigPages $config_page
//     foreach ($config_pages as $config_page) {
//       // Drupal\Core\Field\EntityReferenceFieldItemList $field_news_rss
//       $field_news_rss = $config_page->field_news_rss;
//       $delta = array_search($entity->id(), array_column($field_news_rss->getValue(), 'target_id'));
//       $field_news_rss->removeItem($delta);

//       $config_page_references[$config_page->id()][$hs_importer->id()] = $hs_importer->id();

//       $config_page->save();
//     }

//     $entity->delete();
//   }

// }

// // Change storage settings here
// $field_storage = FieldStorageConfig::loadByName('config_pages', 'field_news_rss');
// $field_storage->setSetting('target_type', 'hs_importer');
// $field_storage->save();

// $field_config =  \Drupal::entityTypeManager()->getStorage('field_config')
//   ->load('config_pages.news_rss.field_news_rss');
// $handler_settings = $field_config->getSetting('handler_settings');
// $handler_settings['target_bundles'] = ['news_rss' => 'news_rss'];
// $field_config->setSetting('handler_settings', $handler_settings);
// $field_config->save();

// foreach ($config_page_references as $config_page_id => $config_page_reference) {
//   // Drupal\config_pages\Entity\ConfigPages $config_page
//   $config_page = \Drupal::entityTypeManager()->getStorage('config_pages')->load($config_page_id);

//   // Drupal\Core\Field\EntityReferenceFieldItemList $field_news_rss
//   $field_news_rss = $config_page->field_news_rss;

//   foreach($config_page_reference as $hs_importer_id) {
//     $field_news_rss->appendItem($hs_importer_id);
//   }

//   $config_page->save();
// }


?>
