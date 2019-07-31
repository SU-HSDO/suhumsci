<?php

namespace Drupal\hs_field_helpers\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;

/**
 * Scales an image resource.
 *
 * @ImageEffect(
 *   id = "hi_rese_image",
 *   label = @Translation("HiRes Image"),
 *   description = @Translation("Display the image 1/2 the actual dimensions to increase pixel density.")
 * )
 */
class HiResImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    array_walk($dimensions, function (&$dimension) {
      $dimension /= 2;
    });
  }

}
