<?php

/**
 * @file
 * hs_paragraph_types.deploy
 */

/**
 * Transfer qualifying content to new field.
 */
function hs_paragraph_types_deploy_hs_clr_band_ttl_field(&$sandbox) {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  if (empty($sandbox['ids'])) {
    $sandbox['ids'] = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_clr_bnd')
      ->condition('field_hs_clr_bnd_txt', 105, '>=')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
  }
  $paragraph_ids = array_splice($sandbox['ids'], 0, 10);

  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  foreach ($paragraph_storage->loadMultiple($paragraph_ids) as $paragraph) {
    $string_to_move = $paragraph->get('field_hs_clr_bnd_txt')->getValue()[0]['value'];
    $paragraph->set('field_hs_clr_bnd_ttl', $string_to_move);
    $paragraph->set('field_hs_clr_bnd_txt', '');
    $paragraph->save();
  }
  $sandbox['#finished'] = count($sandbox['ids']) ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;
}