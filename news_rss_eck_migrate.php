<?php

// @todo Turn this into an updatedb hook.
// @todo Use dependency injection (can I with an updatedb hook?)

echo "Loading existing News RSS ECK entities...";

// Load all existing News RSS ECK entities.
$entity_type_manager = \Drupal::entityTypeManager();
if ($entity_type_manager->hasDefinition('importers')) {
  $storage = $entity_type_manager->getStorage('importers');
  $entities = $storage->loadByProperties(['type' => 'news_rss']);
  foreach ($entities as $entity) {
    // Clone the ECK News RSS entity into a new hs_entity News RSS entity.
    $new_news_rss = \Drupal::entityTypeManager()->getStorage('hs_entity')->create(['bundle' => 'hs_entity_news_rss', 'label' => 'test']);
    // Duplicate the field values from the existing News RSS entity.
    $new_news_rss->set('field_rss_url', $entity->get('field_url')->getValue());
    $new_news_rss->set('field_category_terms', $entity->get('field_terms')->getValue());
    // Save the new entity.
    $new_news_rss->save();
    // Load all News Importer Config Page entities referencing this news_rss item.

    // $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    //   'type' => 'research',
    //   'field_scientists' => $scientist_id,
    // ]);

    // var_dump($entity->get('field_url'));
    // var_dump($entity->get('field_terms'));
  }

}



?>
