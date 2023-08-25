<?php

// @todo Turn this into an updatedb hook.

$entity_type_manager = \Drupal::entityTypeManager();
if ($entity_type_manager->hasDefinition('importers')) {
  // Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage.
  $storage = $entity_type_manager->getStorage('importers');
  $entities = $storage->loadByProperties(['type' => 'news_rss']);
  // Drupal\eck\Entity\EckEntity $entity.
  foreach ($entities as $entity) {
    // Drupal\hs_entities\Entity\HsEntity $hs_entity.
    $hs_entity = \Drupal::entityTypeManager()->getStorage('hs_entity')->create([
      'bundle' => 'hs_entity_news_rss'
    ]);
    $hs_entity->set('field_rss_url', $entity->get('field_url')->getValue());
    $hs_entity->set('field_category_terms', $entity->get('field_terms')->getValue());
    $hs_entity->save();

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

      // Drupal\Core\Field\EntityReferenceFieldItemList $field_hs_news_rss
      $field_hs_news_rss = $config_page->field_hs_news_rss;
      $field_hs_news_rss->appendItem($hs_entity->id());

      $config_page->save();
    }

    $entity->delete();
  }
}




?>
