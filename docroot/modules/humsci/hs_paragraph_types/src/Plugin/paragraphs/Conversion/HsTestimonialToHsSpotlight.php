<?php

namespace Drupal\hs_paragraph_types\Plugin\paragraphs\Conversion;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsConversion;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsConversionBase;

/**
 * Converts hs_testimonial paragraphs to hs_spotlight paragraphs.
 */
#[ParagraphsConversion(
  id: 'hs_testimonial_to_hs_spotlight',
  label: new TranslatableMarkup('Convert Testimonial to Spotlight'),
  source_type: 'hs_testimonial',
  target_types: ['hs_spotlight'],
  weight: 0,
)]
class HsTestimonialToHsSpotlight extends ParagraphsConversionBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $settings, ParagraphInterface $original_paragraph, ?array $converted_paragraphs = NULL) {
    return [
      [
        'type' => 'hs_spotlight',
        'field_hs_spotlight_title' => $original_paragraph->get('field_hs_testimonial_name')->value ?? '',
        'field_hs_spotlight_body' => [
          'value' => $original_paragraph->get('field_hs_testimonial_quote')->value ?? '',
          'format' => 'minimal_html',
        ],
        'field_hs_spotlight_image' => $original_paragraph->get('field_hs_testimonial_image')->getValue(),
        'field_hs_spotlight_link' => $original_paragraph->get('field_hs_testimonial_link')->getValue(),
      ],
    ];
  }

}
