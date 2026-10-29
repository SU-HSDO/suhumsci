<?php

/**
 * @file
 * su_humsci_profile.deploy.php
 *
 * Deploy hooks run after config import. By convention in this project, we use
 * purely numerical suffixes (e.g. _10001) to keep execution order explicit.
 *
 * @see https://github.com/drush-ops/drush/blob/-/drush.api.php
 */

/**
 * Flush WebP derivatives of GIF files so animated GIFs are regenerated.
 *
 * Derivatives created before the imagemagick animation patch (3295470) are
 * single-frame WebP files and are not rebuilt until they are flushed.
 */
function su_humsci_profile_deploy_10001(): string {
  $uris = \Drupal::entityTypeManager()->getStorage('file')->getQuery()
    ->accessCheck(FALSE)
    ->condition('uri', '.gif', 'ENDS_WITH')
    ->execute();
  $uris = array_map(
    fn($file) => $file->getFileUri(),
    \Drupal::entityTypeManager()->getStorage('file')->loadMultiple($uris)
  );

  $styles = [];
  /** @var \Drupal\image\ImageStyleInterface $style */
  foreach (\Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple() as $style) {
    foreach ($style->getEffects() as $effect) {
      $extension = $effect->getConfiguration()['data']['extension'] ?? '';
      if ($effect->getPluginId() === 'image_convert' && strtolower($extension) === 'webp') {
        $styles[] = $style;
        break;
      }
    }
  }

  foreach ($styles as $style) {
    foreach ($uris as $uri) {
      $style->flush($uri);
    }
  }
  return t('Flushed WebP derivatives of @files GIF files in @styles image styles.', [
    '@files' => count($uris),
    '@styles' => count($styles),
  ]);
}
