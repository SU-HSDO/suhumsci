<?php

namespace Drupal\hs_config_readonly\EventSubscriber;

use Drupal\config_readonly\ConfigReadonlyWhitelistTrait;
use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Installed configuration service.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionConfigStorage;

  /**
   * Optional configuration service.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * Ignore the configurations form these modules.
   *
   * @var array
   */
  protected $excludedModules = ['system'];

  /**
   * Root path of Drupal.
   *
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
   * {@inheritdoc}
   */
  public function __construct($drupal_root, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, ExtensionInstallStorage $extension_config_storage, ExtensionInstallStorage $extension_optional_config_storage) {
    $this->drupalRoot = $drupal_root;
    $this->moduleHandler = $module_handler;
    $this->excludedModules = $config_factory->get('hs_config_readonly')
      ->get('excluded_modules') ?: $this->excludedModules;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
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
   * Mark the event form as read only given config conditions.
   *
   * @param \Drupal\config_readonly\ReadOnlyFormEvent $event
   *   The triggered event.
   *
   * @throws \ReflectionException
   */
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
      $mark_form_read_only = $this->configIsLocked($name);
      // If all config is in the whitelist, do not block the form.
      $mark_form_read_only = $this->matchesWhitelistPattern($name) ? FALSE : $mark_form_read_only;
    }

    // Config forms.
    if ($mark_form_read_only && $form_object instanceof ConfigFormBase) {
      $names = $this->getEditableConfigNames($form_object);
      $mark_form_read_only = $this->configIsLocked($names);

      // If all configs are in the whitelist, do not block the form.
      if ($names == array_filter($names, [$this, 'matchesWhitelistPattern'])) {
        $mark_form_read_only = FALSE;
      }
    }

    if ($mark_form_read_only) {
      $event->markFormReadOnly();
    }
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
    // @see \Drupal\config_readonly\EventSubscriber\ReadOnlyFormSubscriber::getEditableConfigNames()
    $reflection = new \ReflectionMethod(get_class($form), 'getEditableConfigNames');
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($form);
  }

  /**
   * Check if the given configuration name can be edited.
   *
   * @param array|string $config
   *   Array of config names.
   *
   * @return bool
   *   If it can be edited.
   */
  protected function configIsLocked($config) {
    $config = is_array($config) ? $config : [$config];
    $locked_config = $this->getLockedConfigs();
    return !empty(array_intersect($config, $locked_config));
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
      if (in_array($name, $this->excludedModules)) {
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
   * @param bool $only_optional
   *   FALSE (default) to list config/install items, TRUE to list
   *   config/optional items.
   *
   * @return string[]
   *   List of config items provided by this extension.
   *
   * @see \Drupal\config_update\ConfigLister::listProvidedItems().
   */
  protected function listProvidedItems($name, $only_optional = FALSE) {
    $pathname = drupal_get_filename('module', $name);
    $component = new Extension($this->drupalRoot, 'module', $pathname);
    if ($only_optional) {
      $names = $this->extensionOptionalConfigStorage->getComponentNames([$component]);
    }
    else {
      $names = $this->extensionConfigStorage->getComponentNames([$component]);
    }
    return array_keys($names);
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesWhitelistPattern($name) {
    // Check for matches.
    $patterns = $this->getWhitelistPatterns();
    if ($patterns) {
      foreach ($patterns as $pattern) {

        // We defined the pattern `*` in the hook to allow config to be changed
        // when the ConfigReadonlyStorage is executed. But for locking down
        // forms, we don't want to whitelist everything. This still gives us
        // the ability to whitelist specific configs via the hook.
        //
        // @see \Drupal\config_readonly\Config\ConfigReadonlyStorage::checkLock()
        // @see hs_config_readonly_config_readonly_whitelist_patterns().
        if ($pattern == '*') {
          continue;
        }

        $escaped = str_replace('\*', '.*', preg_quote($pattern, '/'));
        if (preg_match('/^' . $escaped . '$/', $name)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}