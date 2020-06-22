<?php

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Par of H&S' block.
 *
 * @Block(
 *  id = "part_of_hs",
 *  admin_label = @Translation("Part of H&S Block"),
 * )
 */
class PartOfHsBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The {{ Lockup line 1 }} {{ Lockup line 2 }} is part of the School of Humanities and Sciences.'),
    ];
  }

}
