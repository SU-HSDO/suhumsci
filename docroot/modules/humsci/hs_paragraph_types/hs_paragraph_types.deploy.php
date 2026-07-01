<?php

/**
 * @file
 * hs_paragraph_types.deploy.php
 *
 * Deploy hooks run after config import, making them suitable for operations
 * that depend on configuration existing first (e.g. new content types,
 * vocabularies, or permissions tied to them).
 *
 * hook_deploy_NAME() allows arbitrary machine names for NAME, with execution
 * order determined alphanumerically. By convention in this project, we use
 * purely numerical suffixes (e.g. _10001) rather than descriptive names. This
 * is a deliberate standard to mirror hook_update_N() conventions, keep
 * execution order explicit and predictable, and avoid the ambiguity of relying
 * on alphabetical sorting of arbitrary strings.
 *
 * @see https://github.com/drush-ops/drush/blob/-/drush.api.php
 */

/**
 * Migrate hs_postcard display mode values to new postcard style fields.
 */
function hs_paragraph_types_deploy_10001(array &$sandbox): string {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  if (empty($sandbox['ids'])) {
    $sandbox['ids'] = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_postcard')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['updated'] = 0;
    $sandbox['system_theme'] = \Drupal::config('system.theme')->get('default');
  }

  $paragraph_ids = array_splice($sandbox['ids'], 0, 50);

  foreach ($paragraph_storage->loadMultiple($paragraph_ids) as $paragraph) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    if (
      !$paragraph->hasField('field_hs_postcard_display') ||
      !$paragraph->hasField('field_hs_postcard_layout') ||
      !$paragraph->hasField('field_hs_postcard_alignment') ||
      !$paragraph->hasField('field_hs_postcard_vertical_style')
    ) {
      continue;
    }

    $display = $paragraph->get('field_hs_postcard_display')->value;

    $layout = 'vertical';
    $vertical_style = 'plain';

    if ($display == 'default') {
      if ($sandbox['system_theme'] == 'humsci_colorful') {
        $parent = $paragraph->getParentEntity();
        if (
          $parent &&
          in_array($parent->bundle(), ['hs_collection', 'hs_priv_collection']) &&
          $parent->hasField('field_raised_cards') &&
          $parent->get('field_raised_cards')->value
        ) {
          $vertical_style = 'accent';
        }
      }
    }
    elseif ($display == 'preview') {
      $layout = 'horizontal';
    }
    elseif ($display == 'token') {
      $vertical_style = 'two-color';
    }
    elseif ($display == 'vertical_button_card') {
      $vertical_style = 'stacked';
    }

    $paragraph->set('field_hs_postcard_layout', $layout);
    $paragraph->set('field_hs_postcard_vertical_style', $vertical_style);
    $paragraph->set('field_hs_postcard_alignment', 'left');
    $paragraph->save();
    $sandbox['updated']++;
  }

  $sandbox['#finished'] = count($sandbox['ids']) ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;

  if ($sandbox['#finished'] === 1) {
    if ($sandbox['updated'] !== $sandbox['total']) {
      \Drupal::logger('hs_paragraph_types')->error('Error: failed updating %count hs_postcard paragraphs.', [
        '%count' => $sandbox['total'] - $sandbox['updated'],
      ]);
    }

    return t('Updated %updated of %total hs_postcard paragraphs.', [
      '%updated' => $sandbox['updated'],
      '%total' => $sandbox['total'],
    ]);
  }

  return '';
}
