<?php

namespace Drupal\mrc_ds_blocks;

use Drupal\block\BlockInterface;
use Drupal\block\BlockViewBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManager;

/**
 * Class BlockLazyLoader.
 *
 * @package Drupal\mrc_ds_blocks
 */
class BlockLazyLoader extends BlockViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, LanguageManager $language_manager, ModuleHandler $module_handler) {
    $entity_type = $entity_manager->getStorage('node')->getEntityType();
    parent::__construct($entity_type, $entity_manager, $language_manager);
  }

  /**
   * @param \Drupal\block\BlockInterface $block
   *
   * @return array
   */
  public function buildBlock(BlockInterface $block) {
    return static::buildPreRenderableBlock($block, \Drupal::service('module_handler'));
  }

}
