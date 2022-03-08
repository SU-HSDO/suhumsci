<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\Commands\Drupal\ConfigCommand;

/**
 * Modifies the blt commands for config syncing.
 */
class HsConfigCommands extends ConfigCommand {

  /**
   * @var bool
   */
  protected $configImportPartial;

  /**
   * Imports configuration from the config directory according to cm.strategy.
   *
   * @command drupal:config:import
   * @aliases dci setup:config-import
   *
   * @validateDrushConfig
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Exception
   */
  public function import($options = ['partial' => TRUE]) {
    $this->invokeCommand('drupal:toggle:modules');
    $this->configImportPartial = (bool) $options['partial'];
    parent::import();
  }

  /**
   * Import configuration using config_split module.
   *
   * @param mixed $task
   *   Drush task.
   * @param string $cm_core_key
   *   Cm core key.
   */
  protected function importConfigSplit($task, $cm_core_key) {
    $task->drush("pm-enable")->arg('config_split');

    // Local environments we don't want all the custom site created configs.
    if (($this->getConfigValue('environment') == 'local') && !$this->configImportPartial) {
      $task->drush("config-import")->arg($cm_core_key);
      // Runs a second import to ensure splits are
      // both defined and imported.
      $task->drush("config-import")->arg($cm_core_key);
      return;
    }

    $task->drush("config-import")->arg($cm_core_key)->option('partial');
    // Runs a second import to ensure splits are
    // both defined and imported.
    $task->drush("config-import")->arg($cm_core_key)->option('partial');
  }

  /**
   * {@inheritDoc}
   */
  protected function importCoreOnly($task, $cm_core_key) {
    $this->importConfigSplit($task, $cm_core_key);
  }

}
