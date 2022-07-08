<?php

namespace Drupal\hs_blocks\Controller;

use Drupal\Core\Url;
use Drupal\layout_builder\Controller\ChooseBlockController;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Defines a controller to choose a new block.
 *
 * This is basically the same as the ChooseBlockController except we needed to
 * change the link route to point to our routes.
 *
 * @internal
 */
class HsChooseBlockController extends ChooseBlockController {

  use LayoutRebuildTrait;

  /**
   * {@inheritdoc}
   */
  public function build(SectionStorageInterface $section_storage, $delta, $group) {
    $build['#type'] = 'container';
    $build['#attributes']['class'][] = 'block-categories';

    $definitions = $this->blockManager->getFilteredDefinitions('layout_builder', $this->getAvailableContexts($section_storage), [
      'section_storage' => $section_storage,
      'delta' => $delta,
      'group' => $group,
    ]);
    foreach ($this->blockManager->getGroupedDefinitions($definitions) as $category => $blocks) {
      $build[$category]['#type'] = 'details';
      $build[$category]['#open'] = TRUE;
      $build[$category]['#title'] = $category;
      $build[$category]['links'] = [
        '#theme' => 'links',
      ];
      foreach ($blocks as $block_id => $block) {

        // This is the only difference from parent class.
        $link = [
          'title' => $block['admin_label'],
          'url' => Url::fromRoute('hs_blocks.add_block',
            [
              'section_storage_type' => $section_storage->getStorageType(),
              'section_storage' => $section_storage->getStorageId(),
              'delta' => $delta,
              'group' => $group,
              'plugin_id' => $block_id,
            ]
          ),
        ];
        if ($this->isAjax()) {
          $link['attributes']['class'][] = 'use-ajax';
          $link['attributes']['data-dialog-type'][] = 'dialog';
          $link['attributes']['data-dialog-renderer'][] = 'off_canvas';
        }
        $build[$category]['links']['#links'][] = $link;
      }
    }
    return $build;
  }

  /**
   * Move the select block in the group up one step.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $group
   *   UUID of the group block.
   * @param string $uuid
   *   UUID of the component to move.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  public function moveBlockUp(SectionStorageInterface $section_storage, $delta, $group, $uuid) {
    $this->moveBlock($section_storage, $delta, $group, $uuid);
  }

  /**
   * Move the select block in the group down one step.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $group
   *   UUID of the group block.
   * @param string $uuid
   *   UUID of the component to move.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  public function moveBlockDown(SectionStorageInterface $section_storage, $delta, $group, $uuid) {
    $this->moveBlock($section_storage, $delta, $group, $uuid, 'down');
  }

  /**
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $group
   *   UUID of the group block.
   * @param string $uuid
   *   UUID of the component to move.
   * @param string $direction
   *   Which direction to move the block.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  protected function moveBlock(SectionStorageInterface $section_storage, $delta, $group, $uuid, $direction = 'up') {
    $parent_component = $section_storage->getSection($delta)
      ->getComponent($group);

    $parent_config = $parent_component->get('configuration');
    $parent_config['children'] = $this->arrayShove($parent_config['children'], $uuid, $direction);
    $parent_component->set('configuration', $parent_config);

    self::getLayoutTempstore()->set($section_storage);
    return $this->rebuildAndClose($section_storage);
  }

  /**
   * Grab the layout builder service.
   *
   * @return \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   *   Temporary storage for layout builder.
   */
  protected static function getLayoutTempstore(): LayoutTempstoreRepositoryInterface {
    return \Drupal::service('layout_builder.tempstore_repository');
  }

  /**
   * Move the key from an associative array up or down one.
   *
   * @param array $array
   *   Associative array to modify.
   * @param string|int $selected_key
   *   Array key to be moved.
   * @param string $direction
   *   Directo to move: 'up' or 'down'.
   *
   * @return array
   *   New adjusted array.
   *
   * @link https://beamtic.com/reordering-arrays-php
   */
  protected function arrayShove(array $array, $selected_key, $direction) {
    $new_array = [];

    foreach ($array as $key => $value) {
      if ($key !== $selected_key) {
        $new_array["$key"] = $value;
        $last = ['key' => $key, 'value' => $value];
        unset($array["$key"]);
      }
      else {
        if ($direction !== 'up') {
          // Value of next, moves pointer.
          $next_value = next($array);

          // Key of next
          $next_key = key($array);

          // Check if $next_key is null,
          // indicating there is no more elements in the array.
          if ($next_key !== NULL) {
            // Add -next- to $new_array, keeping -current- in $array.
            $new_array["$next_key"] = $next_value;
            unset($array["$next_key"]);
          }
        }
        else {
          if (isset($last['key'])) {
            unset($new_array["{$last['key']}"]);
          }
          // Add current $array element to $new_array.
          $new_array["$key"] = $value;
          // Re-add $last to $new_array.
          $new_array["{$last['key']}"] = $last['value'];
        }
        // Merge new and old array.
        return $new_array + $array;
      }
    }
  }

}
