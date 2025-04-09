<?php

/**
 * @file
 * Deployment hooks for H&S Paragraph Types module.
 */

/**
 * Update default values for spotlight paragraph fields.
 */
function hs_paragraph_types_deploy_update_spotlight_defaults(&$sandbox) {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

  // Initialize the batch.
  if (!isset($sandbox['total'])) {
    $sandbox['ids'] = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_spotlight')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return t('No spotlight paragraphs found to update.');
    }
  }

  // Process paragraphs in batches of 25.
  $paragraph_ids = array_slice($sandbox['ids'], $sandbox['current'], 25);

  foreach ($paragraph_storage->loadMultiple($paragraph_ids) as $paragraph) {
    $save_needed = FALSE;

    // Update field_hs_spotlight_height if _none or empty.
    if ($paragraph->hasField('field_hs_spotlight_height')) {
      $height_value = $paragraph->get('field_hs_spotlight_height')->value;
      if (empty($height_value) || $height_value === '_none') {
        $paragraph->set('field_hs_spotlight_height', 'default');
        $save_needed = TRUE;
      }
    }

    // Update field_hs_spotlight_image_align if _none or empty.
    if ($paragraph->hasField('field_hs_spotlight_image_align')) {
      $align_value = $paragraph->get('field_hs_spotlight_image_align')->value;
      if (empty($align_value) || $align_value === '_none') {
        $paragraph->set('field_hs_spotlight_image_align', 'image-right');
        $save_needed = TRUE;
      }
    }

    // Update field_spotlight_style if _none or empty.
    if ($paragraph->hasField('field_spotlight_style')) {
      $style_value = $paragraph->get('field_spotlight_style')->value;
      if (empty($style_value) || $style_value === '_none') {
        $paragraph->set('field_spotlight_style', 'classic');
        $save_needed = TRUE;
      }
    }

    if ($save_needed) {
      $paragraph->save();
    }

    $sandbox['current']++;
  }

  // Update progress.
  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);

  if ($sandbox['#finished'] >= 1) {
    return t('Updated @count spotlight paragraphs with new default values.', [
      '@count' => $sandbox['total'],
    ]);
  }
}
