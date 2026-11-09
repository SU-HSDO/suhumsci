<?php

namespace Drupal\hs_field_helpers;

use Drupal\contextual\ContextualLinksSerializer;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Alter data before rendering.
 *
 * @package Drupal\hs_field_helpers
 */
class PreRenderer implements TrustedCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderDsEntity'];
  }

  /**
   * PreRender the ds entity to add contextual links.
   *
   * @param array $element
   *   Entity render array.
   *
   * @return array
   *   Altered render array.
   */
  public static function preRenderDsEntity(array $element): array {
    $module_handler = \Drupal::moduleHandler();
    if (isset($element['#contextual_links']) && $module_handler->moduleExists('contextual')) {
      $placeholder = [
        '#type' => 'contextual_links_placeholder',
        '#id' => \Drupal::service(ContextualLinksSerializer::class)->linksToId($element['#contextual_links']),
      ];
      $element['#prefix'] = \Drupal::service('renderer')->renderInIsolation($placeholder);
    }
    return $element;
  }

}
