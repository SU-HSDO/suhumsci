<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;

/**
 * Defines commands in the "drupal:toggle:modules" namespace.
 */
class ToggleModulesCommand extends BltTasks {

  /**
   * Enables and uninstalls specified modules.
   *
   * You may define the environment for which modules should be toggled by
   * passing the --environment=[value] option to this command, setting the
   * 'environnment' environment variable, or defining environment in one of your
   * BLT configuration files.
   *
   * @command drupal:toggle:modules
   *
   * @aliases dtm toggle setup:toggle-modules
   *
   * @validateDrushConfig
   */
  public function toggleModules() {
    if ($this->getConfig()->has('environment')) {
      $environment = $this->getConfigValue('environment');
    }

    if (isset($environment)) {
      // Enable modules.
      $enable_key = "modules.$environment.enable";
      $this->doToggleModules('pm-enable', $enable_key);

      // Uninstall modules.
      $disable_key = "modules.$environment.uninstall";
      $this->doToggleModules('pm-uninstall', $disable_key);
    }
    else {
      $this->say("Environment is unset. Skipping drupal:toggle:modules...");
    }
  }

  /**
   * Enables or uninstalls an array of modules.
   *
   * @param string $command
   *   The drush command to execute, e.g., pm-enable or pm-uninstall.
   * @param string $config_key
   *   The config key containing the array of modules.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function doToggleModules($command, $config_key) {
    if ($this->getConfig()->has($config_key)) {
      $this->say("Executing <comment>drush $command</comment> for modules defined in <comment>$config_key</comment>...");
      $modules = (array) $this->getConfigValue($config_key);
      $modules_list = implode(' ', $modules);
      $result = $this->taskDrush()
        ->drush("$command $modules_list")
        ->run();
      if (!$result->wasSuccessful()) {
        throw new BltException("Could not toggle modules listed in $config_key.");
      }
    }
    else {
      $this->logger->info("$config_key is not set.");
    }
  }

}
