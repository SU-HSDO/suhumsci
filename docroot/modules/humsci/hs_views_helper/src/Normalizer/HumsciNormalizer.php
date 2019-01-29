<?php

namespace Drupal\hs_views_helper\Normalizer;

use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\serialization\Normalizer\FieldNormalizer;
use Drupal\serialization\Normalizer\MarkupNormalizer;

class HumsciNormalizer extends MarkupNormalizer {

  public function normalize($object, $format = NULL, array $context = []) {
    $normalized = parent::normalize($object, $format, $context);
    if (strpos($normalized, '<ul') !== FALSE) {
//      $normalized = json_encode(['first' => 'first line', '2' => 'second line']);
    }
    return $normalized;
  }

}
