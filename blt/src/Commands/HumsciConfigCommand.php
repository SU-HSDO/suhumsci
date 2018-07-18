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
//
//  find a way to sync to production sites and revert form and view configs.
//  /**
//   * Import any missing entity form/display configs since they are ignored.
//   *
//   * @hook pre-command drupal:config:import
//   */
//  public function preConfigImport() {
//    $configs = $this->taskDrush()
//      ->drush('sqlq')
//      ->arg('select name from config where name like "core.entity_view_display.node.hs_%" or name like "core.entity_form_display.node.hs_%"')
//      ->run()
//      ->getMessage();
//    $configs = array_filter(explode("\n", $configs));
//    foreach ($configs as $config) {
//
//      $uuid = $this->taskDrush()
//        ->drush('cget')
//        ->args([$config, 'uuid'])
//        ->option('format', 'csv')
//        ->run()
//        ->getMessage();
//      $this->taskDrush()->drush('cdel')->arg($config)->run();
//      $this->uuids[$config] = trim($uuid);
//    }
//  }

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

      switch ($strategy) {
        case 'core-only':
          $this->importCoreOnly($task, $cm_core_key);
          break;

        case 'config-split':
          try {
            $this->importConfigSplit($task, $cm_core_key, $options['partial']);
          }
          catch (\Exception $e) {
            $this->say($e->getMessage());
          }
          break;

        case 'features':
          $this->importFeatures($task, $cm_core_key);

          if ($this->getConfigValue('cm.features.no-overrides')) {
            // @codingStandardsIgnoreLine
            $this->checkFeaturesOverrides();
          }
          break;
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
    $task->drush("pm-enable")->arg('config_split');

    // Local environments we don't want all the custom site created configs.
    if ($this->getConfigValue('environment') == 'local' && !$partial) {
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
   * Import any missing entity form/display configs since they are ignored.
   *
   * @hook post-command drupal:config:import
   */
  public function postConfigImport() {
    $this->yell('Importing missing configs!');
    $result = $this->taskDrush()->drush('config-missing-report')->args([
      'type',
      'system.all',
    ])->option('format', 'json')->run();
    $configs = json_decode($result->getMessage(), TRUE);

    // Since we ignore all the entity form and entity display configs, drush cim
    // does not import any new ones. So here we are importing any of those
    // missing configs if they are new.
    foreach ($configs as $item) {
      $name = $item['item'];
      if (strpos($name, 'core.entity_') !== FALSE) {
        $this->taskDrush()->drush('config:import-missing')->arg($name)->run();
      }
    }
  }

}
