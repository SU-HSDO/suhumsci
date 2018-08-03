<?php

namespace Drupal\mrc_ds_blocks;

use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Static methods for Mrc Ds Blocks UI.
 */
class MrcDsBlocks {

  /**
   * Get the field ui route that should be used for given arguments.
   *
   * @param \stdClass $block
   *   The block to get the field ui route for.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getFieldUiRoute($block) {

    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($block->entity_type);
    if ($entity_type->get('field_ui_base_route')) {

      $mode_route_name = "default";
      $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $block->bundle);

      // Get correct route name based on context and mode.
      if ($block->context == 'form') {
        $context_route_name = 'entity_form_display';

        if ($block->mode != 'default') {
          $mode_route_name = 'form_mode';
          $route_parameters['form_mode_name'] = $block->mode;
        }

      }
      else {
        $context_route_name = 'entity_view_display';

        if ($block->mode != 'default') {
          $mode_route_name = 'view_mode';
          $route_parameters['view_mode_name'] = $block->mode;
        }

      }

      return new Url("entity.{$context_route_name}.{$block->entity_type}.{$mode_route_name}", $route_parameters);
    }
  }

  /**
   * Get the delete route for a given block.
   *
   * @param \stdClass $block
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getDeleteRoute($block) {

    $entity_type_id = $block->entity_type;
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->get('field_ui_base_route')) {

      $mode_route_name = '';
      $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $block->bundle);
      $route_parameters['mrc_ds_blocks_id'] = $block->blockId;

      $context_route_name = 'display';
      if ($block->mode != 'default') {
        $mode_route_name = '.view_mode';
        $route_parameters['view_mode_name'] = $block->mode;
      }

      return new Url('mrc_ds_blocks.mrc_ds_blocks_delete_' . $entity_type_id . '.' . $context_route_name . $mode_route_name, $route_parameters);
    }

    throw new \InvalidArgumentException('The given block is not a valid.');
  }

}