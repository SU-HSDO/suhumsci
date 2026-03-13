<?php

namespace Drupal\hs_paragraph_types\Plugin\paragraphs\Conversion;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsConversion;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsConversionBase;

/**
 * Converts hs_spotlight paragraphs to hs_testimonial paragraphs.
 */
#[ParagraphsConversion(
  id: 'hs_spotlight_to_hs_testimonial',
  label: new TranslatableMarkup('Convert Spotlight to Testimonial'),
  source_type: 'hs_spotlight',
  target_types: ['hs_testimonial'],
  weight: 0,
)]
class HsSpotlightToHsTestimonial extends ParagraphsConversionBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $settings, ParagraphInterface $original_paragraph, ?array $converted_paragraphs = NULL) {
    return [
      [
        'type' => 'hs_testimonial',
        'field_hs_testimonial_name' => $original_paragraph->get('field_hs_spotlight_title')->value ?? '',
        'field_hs_testimonial_quote' => strip_tags($original_paragraph->get('field_hs_spotlight_body')->value) ?? '',
        'field_hs_testimonial_image' => $original_paragraph->get('field_hs_spotlight_image')->getValue(),
        'field_hs_testimonial_link' => $original_paragraph->get('field_hs_spotlight_link')->getValue(),
      ],
    ];
  }

}
