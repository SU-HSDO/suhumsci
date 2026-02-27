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
   * {@inheritDoc}
   */
  protected function importConfigSplit($task): void {
    $task->drush('pm-enable')->arg('config_split');

    // Local environments we don't want all the custom site created configs.
    if (($this->getConfigValue('environment') == 'local') && !$this->configImportPartial) {
      $task->drush('config-import');
      // Runs a second import to ensure splits are
      // both defined and imported.
      $task->drush('config-import');
      return;
    }

    // 2026-02-06: Because config_split and config_ignore use config transform
    // in their latest versions, and partial imports do not use config
    // transform, we need to use our custom hs_config_partial module to
    // run partial imports with the split and ignore functionality intact.
    // Enable the partial import through the module setting before running a 
    // regular config import.
    $task->drush('cset')->arg('hs_config_partial.settings')->arg('enabled')->arg(1);
    $task->drush('config-import');
    // Runs a second import to ensure splits are both defined and imported.
    $task->drush('cset')->arg('hs_config_partial.settings')->arg('enabled')->arg(1);
    $task->drush('config-import');
  }

  /**
   * {@inheritDoc}
   */
  protected function importCoreOnly($task): void {
    $task->drush('config-import');
  }

}
