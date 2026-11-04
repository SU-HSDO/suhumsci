<?php

namespace Drupal\hs_algolia;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Swaps in the helper that queues site-scoped Training Hub deletions.
 *
 * @see \Drupal\hs_algolia\HsAlgoliaHelper
 */
class HsAlgoliaServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('search_api_algolia.helper')) {
      $container->getDefinition('search_api_algolia.helper')
        ->setClass(HsAlgoliaHelper::class)
        ->addMethodCall('setSitePath', ['%site.path%']);
    }
  }

}
