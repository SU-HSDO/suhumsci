<?php

namespace Drupal\hs_config_readonly\EventSubscriber;

use Drupal\config_readonly\ConfigReadonlyWhitelistTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\config_filter\Config\FilteredStorageInterface;
use Drupal\config_filter\Plugin\ConfigFilterPluginManager;
use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ConfigReadonlyEventSubscriberBase.
 *
 * @package Drupal\hs_config_readonly\EventSubscriber
 */
abstract class ConfigReadonlyEventSubscriberBase implements EventSubscriberInterface {

  use ConfigReadonlyWhitelistTrait;

  /**
   * Config filter storage service.
   *
   * @var \Drupal\config_filter\Config\FilteredStorageInterface
   */
  protected $configStorage;

  /**
   * Config filter plugin manager service.
   *
   * @var \Drupal\config_filter\Plugin\ConfigFilterPluginManager
   */
  protected $configFilterManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Ignore the configurations form these modules.
   *
   * @var array
   */
  protected $excludedModules = ['system'];

  /**
   * Form ids to mark as read only.
   *
   * @var array
   */
  protected $readOnlyFormIds = [
    'config_single_import_form',
    'system_modules_uninstall',
  ];

  /**
   * Form IDs that we find that can be bypassed such as views duplication.
   *
   * @var array
   */
  protected $bypassFormIds = [
    'view_duplicate_form',
    'menu_edit_form',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, FilteredStorageInterface $config_storage, ConfigFilterPluginManager $filter_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->configStorage = $config_storage;
    $this->configFilterManager = $filter_manager;
    $config = $config_factory->get('hs_config_readonly.settings');
    $this->excludedModules = $config->get('excluded_modules') ?: $this->excludedModules;
    $this->readOnlyFormIds = $config->get('form_ids') ?: $this->readOnlyFormIds;
    $this->bypassFormIds = $config->get('bypass_form_ids') ?: $this->bypassFormIds;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReadOnlyFormEvent::NAME][] = ['onFormAlter', 200];
    return $events;
  }

}
