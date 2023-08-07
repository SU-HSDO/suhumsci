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
    // Load all News RSS Config Page entities referencing this news_rss item.
    $news_rss_config_pages = \Drupal::entityTypeManager()->getStorage('config_pages')->loadByProperties([
      'type' => 'news_rss',
      'field_news_rss' => $entity->id(),
    ]);
    // Loop through the News RSS Config Page entities.
    foreach($news_rss_config_pages as $news_rss_config_pages) {
      // Add the new hs_entity News RSS entity into the new News RSS field on
      // the News RSS config page.
      // The field is a multi-value listItem so need to pull the value(s), then
      // add the new value, then set the field value, then save the entity.
      $news_rss_config_pages->field_hs_news_rss->appendItem($new_news_rss->id());
      $news_rss_config_pages->save();

      // $field_hs_news_rss = $news_rss_config_pages->get('field_hs_news_rss');
      // $field_hs_news_rss->appendItem($new_news_rss->id());
      // $news_rss_config_pages->set('field_hs_news_rss', $field_hs_news_rss);
      //
    }
  }

}



?>
