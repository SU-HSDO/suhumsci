<?php

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'HsBackToTopBlock' block.
 *
 * @Block(
 *  id = "hs_blocks_back_to_top_block",
 *  admin_label = @Translation("Back To Top Block"),
 * )
 */
class HsBackToTopBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'back_to_top' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => ['#main-content'],
          'class' => ['hs-back-to-top'],
        ],
        '#value' => $this->t('Back to Top'),
        '#attached' => [
          'library' => [
            'hs_blocks/back_to_top',
          ],
        ],
      ],
    ];
  }

}
