<?php

namespace Example\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Serialization\Yaml;

/**
 * This class defines example hooks.
 */
class HumsciHooksCommands extends BltTasks {

  /**
   * After a multisite is created, modify the drush alias with default values.
   *
   * @hook post-command recipes:multisite:init
   */
  public function postMultiSiteInit() {
    $root = $this->getConfigValue('repo.root');
    $multisites = [];

    $default_alias = Yaml::decode(file_get_contents("$root/drush/sites/default.site.yml"));
    $sites = glob("$root/drush/sites/*.site.yml");

    foreach ($sites as $site_file) {
      $alias = Yaml::decode(file_get_contents($site_file));
      preg_match('/sites\/(.*)\.site\.yml/', $site_file, $matches);
      $site_name = $matches[1];

      $multisites[] = $site_name;
      if (count($alias) != count($default_alias)) {
        foreach ($default_alias as $environment => $env_alias) {
          $env_alias['uri'] = $this->getAliasUrl($site_name, $environment);
          $alias[$environment] = $env_alias;
        }
      }

      file_put_contents($site_file, Yaml::encode($alias));
    }

    // Add the site to the multisites in BLT's configuration.
    $root = $this->getConfigValue('repo.root');
    $blt_config = Yaml::decode(file_get_contents("$root/blt/blt.yml"));
    asort($multisites);
    $blt_config['multisites'] = array_unique($multisites);
    file_put_contents("$root/blt/blt.yml", Yaml::encode($blt_config));

    $create_db = $this->ask('Would you like to create the database on Acquia now? (y/n)');
    if (substr(strtolower($create_db), 0, 1) == 'y') {
      $this->invokeCommand('humsci:create-database');
    }
  }

  protected function getAliasUrl($site_name, $environment) {
    $site_name = str_replace('_', '-', str_replace('__', '.', $site_name));
    if ($environment == 'local') {
      return $site_name;
    }

    $site_url = explode('.', $site_name, 2);
    if (count($site_url) >= 2) {
      [$site, $subdomain] = $site_url;
      return "$site-$environment.$subdomain.stanford.edu";
    }
    return "$site_name-$environment.stanford.edu";
  }

  /**
   * Disables saml & log in when drupal:sync finishes.
   *
   * @hook post-command drupal:sync
   */
  public function postDrupalSync($result, CommandData $commandData) {
    $this->taskDrush()
      ->drush('pmu')
      ->arg('simplesamlphp_auth')
      ->option('yes')
      ->run();
    $this->taskDrush()
      ->drush('pmu')
      ->arg('shield')
      ->option('yes')
      ->run();
    $this->taskDrush()->drush('uli')->run();
  }

  /**
   * Toggle modules first.
   *
   * @hook pre-command drupal:config:import
   */
  public function preConfigImport() {
    $this->invokeCommand('drupal:toggle:modules');
  }

  /**
   * Update database first.
   *
   * @hook pre-command drupal:toggle:modules
   */
  public function preToggleModules() {
    $this->taskDrush()->drush('updb')->run();
  }

  /**
   * Import any missing entity form/display configs since they are ignored.
   *
   * @hook post-command drupal:config:import
   */
  public function postConfigImport() {
    $this->yell('Importing new form and display configuration items that don\'t exist in the database because they are ignored in config.ignore');
    $result = $this->taskDrush()
      ->drush('config-missing-report')
      ->args([
        'type',
        'system.all',
      ])
      ->option('format', 'string')
      ->printOutput(FALSE)
      ->run();
    $configs = array_filter(explode("\n", $result->getMessage()));

    // Since we ignore all the entity form and entity display configs, drush cim
    // does not import any new ones. So here we are importing any of those
    // missing configs if they are new.
    foreach ($configs as $name) {
      if (strpos($name, 'core.entity_') !== FALSE) {
        $this->taskDrush()->drush('config:import-missing')->arg($name)->run();
      }
    }
  }

}
