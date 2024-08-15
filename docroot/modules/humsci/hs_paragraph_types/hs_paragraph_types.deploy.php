<?php

/**
 * @file
 * Drush deploy hooks for hs_paragraph_types module.
 */

/**
 * Copy old background color field data to new background field.
 */
function hs_paragraph_types_deploy_10000() {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  $collection_paragraphs = $paragraph_storage->loadByProperties(['type' => 'hs_collection']);
  foreach ($collection_paragraphs as $paragraph) {
    /** @var Drupal\paragraphs\Entity\Paragraph $paragraph */
    if (
      $paragraph->hasField('field_paragraph_style') &&
      $paragraph->hasField('field_bg_color') &&
      $paragraph->hasField('field_bg_color_width')) {
      // Only set new field if old field had data.
      if ($paragraph->get('field_paragraph_style')->value) {
        $paragraph->set('field_bg_color', 'color_palette_default');
      }
      // Sets default for background width always.
      $paragraph->set('field_bg_color_width', 'limited_width');
      $paragraph->save();
    }
  }
}
