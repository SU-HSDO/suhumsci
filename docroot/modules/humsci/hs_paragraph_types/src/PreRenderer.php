<?php

namespace Drupal\hs_paragraph_types;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Alter paragraph widget data before rendering.
 *
 * @package Drupal\hs_paragraph_types
 */
class PreRenderer implements TrustedCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderCarouselSlideLabel'];
  }

  /**
   * Re-apply the text-box carousel slide label in the editor.
   *
   * The slides of a "Banner image(s) with text box" (hs_carousel) are
   * hs_hero_image ("Banner image with full overlay and text") paragraphs.
   * paragraphs_ee's hook_field_widget_complete_form_alter() resets the
   * collapsed label to that paragraph type label after
   * hs_paragraph_types_field_widget_complete_paragraphs_form_alter() runs, so
   * this restores the carousel's label at render time.
   *
   * @param array $element
   *   The label render element.
   *
   * @return array
   *   The label render element.
   */
  public static function preRenderCarouselSlideLabel(array $element): array {
    $element['#markup'] = '<span class="paragraph-type-label">' . t('Banner image with text box') . '</span>';
    return $element;
  }

}
