<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\Commands\Setup\ConfigCommand;
use Acquia\Blt\Robo\Exceptions\BltException;

/**
 * Class HumsciCommands.
 *
 * @package Acquia\Blt\Custom\Commands
 */
class HumsciConfigCommand extends ConfigCommand {

  protected $uuids = [];

  /**
   * Copy a given module configs from default into the module.
   *
   * @command drupal:config:module
   *
   * @param string $module_name
   */
  public function copyConfigToModule($module_name) {
    $info_file = $this->rglob($this->getConfigValue('docroot') . '/*/humsci/*/' . $module_name . '*.yml');
    $module_path = str_replace("/$module_name.info.yml", '', reset($info_file));
    foreach ($this->rglob("$module_path/config/*/*.yml") as $config) {
      $file_name = substr($config, strrpos($config, '/') + 1);
      if (file_exists("config/default/$file_name")) {
        copy("config/default/$file_name", $config);
      }
    }
  }

  /**
   * @param $pattern
   * @param int $flags
   *
   * @return array|void
   */
  protected function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
  }

  /**
   * Imports configuration from the config directory according to cm.strategy.
   *
   * @command drupal:config:import
   * @aliases dci setup:config-import
   *
   * @option partial Don't delete existing configuration.
   *
   * @validateDrushConfig
   * @executeInVm
   */
  public function import($options = ['partial' => FALSE]) {
    $strategy = $this->getConfigValue('cm.strategy');
    $cm_core_key = $this->getConfigValue('cm.core.key');
    $this->logConfig($this->getConfigValue('cm'), 'cm');

    if ($strategy != 'none') {
      $this->invokeHook('pre-config-import');

      $task = $this->taskDrush()
        ->stopOnFail()
        // Sometimes drush forgets where to find its aliases.
        ->drush("cc")->arg('drush')
        // Rebuild caches in case service definitions have changed.
        // @see https://www.drupal.org/node/2826466
        ->drush("cache-rebuild")
        // Execute db updates.
        // This must happen before features are imported or configuration is
        // imported. For instance, if you add a dependency on a new extension to
        // an existing configuration file, you must enable that extension via an
        // update hook before attempting to import the configuration.
        // If a db update relies on updated configuration, you should import the
        // necessary configuration file(s) as part of the db update.
        ->drush("updb");

      // If exported site UUID does not match site active site UUID, set active
      // to equal exported.
      // @see https://www.drupal.org/project/drupal/issues/1613424
      $exported_site_uuid = $this->getExportedSiteUuid($cm_core_key);
      if ($exported_site_uuid) {
        $task->drush("config:set system.site uuid $exported_site_uuid");
      }

      try {
        $this->importConfigSplit($task, $cm_core_key, $options['partial']);
      }
      catch (\Exception $e) {
        $this->say($e->getMessage());
      }

      $task->drush("cache-rebuild");
      $result = $task->run();
      if (!$result->wasSuccessful()) {
        $this->say("Failed to import configuration!");
      }

      $this->checkConfigOverrides($cm_core_key);

      $result = $this->invokeHook('post-config-import');

      return $result;
    }
  }

  /**
   * Import configuration using config_split module.
   *
   * @param \Acquia\Blt\Robo\Tasks\DrushTask $task
   * @param string $cm_core_key
   * @param bool $partial
   */
  protected function importConfigSplit($task, $cm_core_key, $partial = FALSE) {
    if ($this->input()->hasOption('partial')) {
      $partial = $this->input()->getOption('partial');
    }
    $task->drush("pm-enable")->arg('config_split');

    // Local environments we don't want all the custom site created configs.
    if (($this->getConfigValue('environment') == 'local' || $this->getConfigValue('environment') == 'dev') && !$partial) {
      $this->taskDrush()
        ->drush('sqlq')
        ->arg('truncate webform_submission')
        ->run();
      $this->taskDrush()
        ->drush('sqlq')
        ->arg('truncate webform_submission_data')
        ->run();
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

}
