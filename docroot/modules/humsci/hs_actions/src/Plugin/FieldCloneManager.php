<?php

namespace Drupal\hs_actions\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\hs_actions\Annotation\FieldClone;
use Drupal\hs_actions\Plugin\Action\FieldClone\FieldCloneInterface;

/**
 * Class CloneFieldsManager for field clone plugins.
 */
class FieldCloneManager extends DefaultPluginManager implements FieldCloneManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Action/FieldClone',
      $namespaces,
      $module_handler,
      FieldCloneInterface::class,
      FieldClone::class
    );
    $this->alterInfo('field_clone_info');
    $this->setCacheBackend($cache_backend, 'field_clone_info_plugins');
  }

}
