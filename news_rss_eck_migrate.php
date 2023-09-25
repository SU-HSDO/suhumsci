<?php

// @todo Turn this into an updatedb hook.

$config_page_references = [];

$entity_type_manager = \Drupal::entityTypeManager();
if ($entity_type_manager->hasDefinition('importers')) {
  // Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage.
  $storage = $entity_type_manager->getStorage('importers');
  $entities = $storage->loadByProperties(['type' => 'news_rss']);
  // Drupal\eck\Entity\EckEntity $entity.
  foreach ($entities as $entity) {
    // Drupal\hs_entities\Entity\HsImporter $hs_importer.
    $hs_importer = \Drupal::entityTypeManager()->getStorage('hs_importer')->create([
      'bundle' => 'news_rss'
    ]);
    $hs_importer->set('field_url', $entity->get('field_url')->getValue());
    $hs_importer->set('field_terms', $entity->get('field_terms')->getValue());
    $hs_importer->save();

    $config_pages = \Drupal::entityTypeManager()->getStorage('config_pages')->loadByProperties([
      'type' => 'news_rss',
      'field_news_rss' => $entity->id(),
    ]);
    // Drupal\config_pages\Entity\ConfigPages $config_page
    foreach ($config_pages as $config_page) {
      // Drupal\Core\Field\EntityReferenceFieldItemList $field_news_rss
      $field_news_rss = $config_page->field_news_rss;
      $delta = array_search($entity->id(), array_column($field_news_rss->getValue(), 'target_id'));
      $field_news_rss->removeItem($delta);

      $config_page_references[$config_page->id()][] = $hs_importer->id();

      $config_page->save();
    }

    $entity->delete();
  }

  // Change storage settings here



  // foreach ($config_page_references as $config_page_id => $config_page_reference) {
  //   // Drupal\config_pages\Entity\ConfigPages $config_page
  //   $config_page = \Drupal::entityTypeManager()->getStorage('config_pages')->load($config_page_id);

  //   // Drupal\Core\Field\EntityReferenceFieldItemList $field_news_rss
  //   $field_news_rss = $config_page->field_news_rss;

  //   foreach($config_page_reference as $hs_importer_id) {
  //     $field_news_rss->appendItem($hs_importer->id());
  //   }

  //   $config_page->save();
  // }
}




?>
