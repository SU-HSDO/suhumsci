<?php

namespace Drupal\hs_config_readonly\EventSubscriber;

use Drupal\config_readonly\ConfigReadonlyWhitelistTrait;
use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Class ConfigReadOnlyEventSubscriber.
 *
 * This event listener replaces
 * \Drupal\config_readonly\EventSubscriber\ReadOnlyFormSubscriber because it
 * gives a "whitelist" capability but we want it to add a blacklist to lock
 * configurations provided by modules.
 *
 * @package Drupal\hs_config_readonly\EventSubscriber
 */
class ConfigReadOnlyEventSubscriber implements EventSubscriberInterface {

  use ConfigReadonlyWhitelistTrait;

  /**
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionConfigStorage;

  /**
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * @var array
   */
  protected $excludedModules = ['system'];

  /**
   * @var string
   */
  protected $drupalRoot;

  /**
   * Form ids to mark as read only.
   */
  protected $readOnlyFormIds = [
    'config_single_import_form',
    'system_modules',
    'system_modules_uninstall',
    'user_admin_permissions',
  ];

  /**
   * Form ids to skip marking as read only.
   */
  protected $formIdExceptions = [
    'search_form',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct($drupal_root, ModuleHandlerInterface $module_handler, ExtensionInstallStorage $extension_config_storage, ExtensionInstallStorage $extension_optional_config_storage) {
    $this->drupalRoot = $drupal_root;
    $this->moduleHandler = $module_handler;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
  }

  public function onFormAlter(ReadOnlyFormEvent $event) {
    // Check if the form is a ConfigFormBase or a ConfigEntityListBuilder.
    $form_object = $event->getFormState()->getFormObject();
    $mark_form_read_only = $form_object instanceof ConfigFormBase;

    if (!$mark_form_read_only) {
      $mark_form_read_only = in_array($form_object->getFormId(), $this->readOnlyFormIds);
    }

    // Check if the form is an EntityFormInterface and entity is a config
    // entity.
    if (!$mark_form_read_only && $form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
      $mark_form_read_only = $entity instanceof ConfigEntityInterface;
    }

    // Don't block particular patterns.
    if ($mark_form_read_only && $form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
      $name = $entity->getConfigDependencyName();

      // Block config from a module.
      $mark_form_read_only = $this->configIsEditable([$name]);

      // Unless its defined as a whiteist pattern.
      if ($this->matchesWhitelistPattern($name)) {
        $mark_form_read_only = FALSE;
      }
    }

    // Config forms.
    if ($mark_form_read_only && $form_object instanceof ConfigFormBase) {
      $editable_config = $this->getEditableConfigNames($form_object);
      $mark_form_read_only = $this->configIsEditable($editable_config);
    }

    if ($mark_form_read_only) {
      $event->markFormReadOnly();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReadOnlyFormEvent::NAME][] = ['onFormAlter', 200];
    return $events;
  }

  /**
   * Get the editable configuration names.
   *
   * @param ConfigFormBase $form
   *   The configuration form.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   *
   * @throws \ReflectionException
   */
  protected function getEditableConfigNames(ConfigFormBase $form) {
    // Use reflection to work around getEditableConfigNames() as protected.
    // @todo Review in 9.x for API change.
    // @see https://www.drupal.org/node/2095289
    $reflection = new \ReflectionMethod(get_class($form), 'getEditableConfigNames');
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($form);
  }

  /**
   * Check if the given configuration name can be edited.
   *
   * @param array $config
   *   Array of config names.
   *
   * @return bool
   *   If it can be edited.
   */
  protected function configIsEditable(array $config) {
    if ($config == array_filter($config, [$this, 'isEditableConfig'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $name
   *
   * @return bool
   */
  protected function isEditableConfig($name) {
    foreach ($this->getLockedConfigs() as $config) {
      if ($config == $name) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get all configs provided by modules.
   *
   * @return array
   *   Config names.
   */
  protected function getLockedConfigs() {
    $module_configs = [];
    foreach ($this->moduleHandler->getModuleList() as $name => $extension) {
      if ($extension->getType() == 'profile' || in_array($name, $this->excludedModules)) {
        continue;
      }

      $install_list = $this->listProvidedItems($name);
      $optional_list = $this->listProvidedItems($name, TRUE);
      $module_configs = array_merge($module_configs, $install_list, $optional_list);
    }
    return $module_configs;
  }

  /**
   * Returns a list of the install storage items for an extension.
   *
   * @param string $name
   *   Machine name of extension.
   * @param bool $do_optional
   *   FALSE (default) to list config/install items, TRUE to list
   *   config/optional items.
   *
   * @return string[]
   *   List of config items provided by this extension.
   */
  protected function listProvidedItems($name, $do_optional = FALSE) {
    $pathname = drupal_get_filename('module', $name);
    $component = new Extension($this->drupalRoot, 'module', $pathname);
    if ($do_optional) {
      $names = $this->extensionOptionalConfigStorage->getComponentNames([$component]);
    }
    else {
      $names = $this->extensionConfigStorage->getComponentNames([$component]);
    }
    return array_keys($names);
  }

}