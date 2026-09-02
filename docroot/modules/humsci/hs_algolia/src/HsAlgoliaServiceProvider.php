<?php

namespace Drupal\hs_algolia;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces the Algolia helper so deletions happen immediately.
 */
class HsAlgoliaServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('search_api_algolia.helper')) {
      $definition = $container->getDefinition('search_api_algolia.helper');
      $definition->setClass(SearchApiAlgoliaHelper::class);
    }
  }

}
