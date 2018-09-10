<?php

namespace Drupal\hs_config_readonly\EventSubscriber;

use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\ctools\Wizard\EntityFormWizardBase;
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
class ConfigReadOnlyEventSubscriber extends ConfigReadonlyEventSubscriberBase {

  /**
   * Mark the event form as read only given config conditions.
   *
   * @param \Drupal\config_readonly\ReadOnlyFormEvent $event
   *   The triggered event.
   */
  public function onFormAlter(ReadOnlyFormEvent $event) {
    // Check if the form is a ConfigFormBase or a ConfigEntityListBuilder.
    $form_object = $event->getFormState()->getFormObject();
    $mark_form_read_only = $form_object instanceof ConfigFormBase;

    // Some forms are safe to allow to the user because they duplicate configs,
    // not modify them.
    if (in_array($form_object->getFormId(), $this->bypassFormIds)) {
      return;
    }

    if (!$mark_form_read_only) {
      $mark_form_read_only = in_array($form_object->getFormId(), $this->readOnlyFormIds);
    }
    $this->checkFormObjects($mark_form_read_only, $form_object);

    if ($mark_form_read_only) {
      $event->markFormReadOnly();
    }
  }

  /**
   * Check the form object.
   *
   * @param bool $mark_readonly
   *   If the form is already marked readonly.
   * @param object $form_object
   *   Form state object.
   */
  protected function checkFormObjects(&$mark_readonly, $form_object) {
    // Check if the form is an EntityFormInterface and entity is a config
    // entity.
    if (!$mark_readonly && $form_object instanceof EntityFormInterface) {
      $mark_readonly = $this->lockEntityFormInterface($form_object);
    }

    if (!$mark_readonly && $form_object instanceof EntityFormWizardBase) {
      $mark_readonly = $this->lockEntityFormWizard($form_object);
    }

    // Config forms.
    if ($mark_readonly && $form_object instanceof ConfigFormBase) {
      $mark_readonly = $this->lockConfigFormBase($form_object);
    }
  }

  /**
   * Should an entity form be locked.
   *
   * @param \Drupal\Core\Entity\EntityFormInterface $form_object
   *   Form state object.
   *
   * @return bool
   *   If the form should be marked readonly.
   */
  protected function lockEntityFormInterface(EntityFormInterface $form_object) {
    $entity = $form_object->getEntity();
    $mark_form_read_only = $entity instanceof ConfigEntityInterface;

    // Don't block particular patterns.
    if ($mark_form_read_only) {
      $entity = $form_object->getEntity();
      $name = $entity->getConfigDependencyName();

      // Block config from a module.
      $mark_form_read_only = $this->configIsLocked($name);
      // If all config is in the whitelist, do not block the form.
      $mark_form_read_only = $this->matchesWhitelistPattern($name) ? FALSE : $mark_form_read_only;
    }
    return $mark_form_read_only;
  }

  /**
   * Check the entity form wizard if it should be locked.
   *
   * @param \Drupal\ctools\Wizard\EntityFormWizardBase $form_object
   *   Form state object.
   *
   * @return bool
   *   If the form should be marked readonly.
   */
  protected function lockEntityFormWizard(EntityFormWizardBase $form_object) {
    try {
      $name = $this->entityTypeManager->getStorage($form_object->getEntityType())
        ->load($form_object->getMachineName())->getConfigDependencyName();
      // Block config from a module.
      $mark_form_read_only = $this->configIsLocked($name);
      // If all config is in the whitelist, do not block the form.
      return $this->matchesWhitelistPattern($name) ? FALSE : $mark_form_read_only;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Should a simple config form be locked.
   *
   * @param \Drupal\Core\Form\ConfigFormBase $form_object
   *   Form state object.
   *
   * @return bool
   *   if the form should be marked readonly.
   */
  protected function lockConfigFormBase(ConfigFormBase $form_object) {
    try {
      $names = $this->getEditableConfigNames($form_object);
    }
    catch (\ReflectionException $e) {
      return FALSE;
    }

    $mark_form_read_only = $this->configIsLocked($names);
    // If all configs are in the whitelist, do not block the form.
    if ($names == array_filter($names, [$this, 'matchesWhitelistPattern'])) {
      $mark_form_read_only = FALSE;
    }
    return $mark_form_read_only;
  }

  /**
   * Get the editable configuration names.
   *
   * @param \Drupal\Core\Form\ConfigFormBase $form
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getLockedConfigs() {
    $configs = $this->configStorage->listAll();
    if (!$this->configFilterManager->hasDefinition('config_ignore')) {
      return $configs;
    }

    /** @var \Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter $plugin */
    $plugin = $this->configFilterManager->createInstance('config_ignore');
    foreach ($plugin->filterListAll('', []) as $ignored_config) {
      $pos = array_search($ignored_config, $configs);
      if ($pos !== FALSE) {
        unset($configs[$pos]);
      }
    }
    return $configs;
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
