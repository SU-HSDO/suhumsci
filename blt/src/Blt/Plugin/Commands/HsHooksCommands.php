<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;

/**
 * BLT command hooks.
 */
class HsHooksCommands extends BltTasks {

  /**
   * @hook pre-command drupal:sync:default:site
   */
  public function preSiteCopy() {
    $root = $this->getConfigValue('repo.root');
    $this->taskExec("cp $root/config/default/config_ignore.settings.yml $root/config/config_ignore.settings.yml")
      ->taskExec("cp $root/config/envs/prod/config_ignore.settings.yml $root/config/default/config_ignore.settings.yml")
      ->run();
  }

  /**
   * @hook post-command drupal:sync:default:site
   */
  public function postSiteCopy() {
    $root = $this->getConfigValue('repo.root');
    $this->taskExec("mv $root/config/config_ignore.settings.yml $root/config/default/config_ignore.settings.yml")
      ->run();
  }

  /**
   * Pre site update command.
   *
   * When deploying code to Acquia, before updating every site with configs,
   * run database updates first to avoid any breaking issues.
   *
   * @hook pre-command artifact:update:drupal:all-sites
   */
  public function preUpdateAllSites() {
    // Disable alias since we are targeting specific uri.
    $this->config->set('drush.alias', '');

    foreach ($this->getConfigValue('multisites') as $multisite) {
      $this->switchSiteContext($multisite);

      if ($this->getInspector()->isDrupalInstalled()) {
        $this->say("Running database updates on <comment>$multisite</comment>...");
        $this->taskDrush()
          ->drush("updb")
          ->run();
      }
    }
  }

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

      if ($site_name == 'mrc') {
        continue;
      }

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

  /**
   * Get the url for the drush alias.
   *
   * @param string $site_name
   *   Site machine name, same as the directory.
   * @param string $environment
   *   Acquia environment.
   *
   * @return string
   *   Url that can be used in drush.
   */
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

}
