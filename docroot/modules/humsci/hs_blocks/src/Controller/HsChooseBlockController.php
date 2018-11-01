<?php

namespace Drupal\hs_blocks\Controller;

use Drupal\Core\Url;
use Drupal\layout_builder\Controller\ChooseBlockController;
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

}
