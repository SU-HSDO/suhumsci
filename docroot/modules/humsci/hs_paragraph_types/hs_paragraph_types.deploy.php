<?php

/**
 * @file
 * Deploy hooks for paragraph components.
 */

/**
 * Update existing hs_collection content if raised cards is enabled.
 */
function hs_paragraph_types_deploy_hs_collection_uh_field(&$sandbox) {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  if (empty($sandbox['ids'])) {
    $sandbox['ids'] = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_collection')
      ->condition('field_raised_cards', TRUE)
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
  }
  $paragraph_ids = array_splice($sandbox['ids'], 0, 10);

  foreach ($paragraph_storage->loadMultiple($paragraph_ids) as $paragraph) {
    $paragraph->set('field_hs_collection_uh', TRUE);
    $paragraph->save();
  }

  $sandbox['#finished'] = count($sandbox['ids']) ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;
}
