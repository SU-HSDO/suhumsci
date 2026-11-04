<?php

/**
 * @file
 * su_humsci_profile.deploy.php
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
 * Remove unused postcard image styles.
 */
function su_humsci_profile_deploy_11001(): string {
  // List of unused image styles to remove.
  $image_styles_to_remove = [
    'postcard_horizontal_356px_',
    'postcard_horizontal_442px_',
    'postcard_horizontal_488px_',
    'postcard_horizontal_497px_',
    'postcard_horizontal_688px_',
    'postcard_horizontal_lg_2x_924px',
    'postcard_horizontal_md_2x_750px',
    'postcard_horizontal_sm_2x_1424px',
    'postcard_horizontal_xl_2x_976px',
    'postcard_horizontal_xs_1x_1040px',
    'postcard_vertical_497px_',
    'postcard_vertical_657px_',
    'postcard_vertical_881px_',
    'postcard_vertical_1088px_',
    'postcard_vertical_1200px_',
    'postcard_vertical_lg_2x_2270px',
    'postcard_vertical_md_2x_1856px',
    'postcard_vertical_sm_2x_1424px',
    'postcard_vertical_xl_2x_2400px',
    'postcard_vertical_xs_2x_1040px',
  ];

  $logger = \Drupal::logger('su_humsci_profile');
  $entity_type_manager = \Drupal::entityTypeManager();
  $deleted = 0;
  $failed = 0;

  // Then remove the actual image styles.
  $image_style_storage = $entity_type_manager->getStorage('image_style');
  foreach ($image_styles_to_remove as $style_id) {
    try {
      if ($style = $image_style_storage->load($style_id)) {
        $style->delete();
        $deleted++;
      }
    }
    catch (\Exception $e) {
      $failed++;
      $logger->error('Failed to delete image style "@id": @error', [
        '@id' => $style_id,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  // Final summary log.
  if ($deleted || $failed) {
    return t('Image style cleanup completed. @deleted styles deleted, @failed failures.', [
      '@deleted' => $deleted,
      '@failed' => $failed,
    ]);
  }
  else {
    return 'Image style cleanup: No styles found to delete.';
  }
}
