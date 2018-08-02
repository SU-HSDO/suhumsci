<?php

namespace Drupal\views_block_filter_block\Plugin\views;

use Drupal\views\Plugin\views\display\Block;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "vbfb_plugin_display_block",
 *   title = @Translation("Block"),
 *   help = @Translation("Display the view as a block."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block")
 * )
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class vbfbPluginDisplayBlock extends Block {

  /**
   * Allows block views to put exposed filter forms in blocks.
   */
  public function usesExposedFormInBlock() {
    return TRUE;
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
    /** @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->filter as $filter) {
      if ($filter->isExposed()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
