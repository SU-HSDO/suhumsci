<?php

namespace Drupal\mrc_ds_blocks;

use Drupal\block\BlockInterface;
use Drupal\block\BlockViewBuilder;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Theme\Registry;

/**
 * Class BlockLazyLoader.
 *
 * @package Drupal\mrc_ds_blocks
 */
class BlockLazyLoader extends BlockViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityRepositoryInterface $entityRepository, LanguageManager $language_manager, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository) {
    $entity_type = $entity_manager->getStorage('node')->getEntityType();
    parent::__construct($entity_type, $entityRepository, $language_manager, $theme_registry, $entity_display_repository);
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
