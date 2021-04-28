<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;

/**
 * Various BLT commands for H&S stack.
 */
class HsCommands extends BltTasks {

  use HsCommandTrait;

  /**
   * Set up local blt settings and necessary files.
   *
   * @command humsci:local:setup
   */
  public function localSetup() {
    $repo_root = $this->getConfigValue('repo.root');
    if (file_exists("$repo_root/blt/local.blt.yml")) {
      $continue = $this->confirm('Local settings have already been set. Do you wish to remove them and start over?', TRUE);
      if (!$continue) {
        return;
      }
    }
    $dir = basename($repo_root);
    $db_name = $this->askDefault('Database Name?', 'suhumsci');
    $db_user = $this->askDefault('Database User Name?', 'root');
    $db_pass = $this->askDefault('Database Password?', 'password');
    $domain = $this->askDefault('Local Site Domain?', "docroot.$dir.loc");

    $data = [
      'project' => ['local' => ['uri' => $domain, 'hostname' => $domain]],
      'drupal' => [
        'db' => [
          'database' => $db_name,
          'username' => $db_user,
          'password' => $db_pass,
          'host' => 'localhost',
          'port' => 3306,
        ],
      ],
    ];

    file_put_contents("$repo_root/blt/local.blt.yml", Yaml::encode($data));
    $this->invokeCommands(['sws:keys', 'sbsc', 'settings']);
  }

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys
   * @description stuff
   */
  public function humsciKeys() {
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.prod:/mnt/gfs/swshumsci.prod/nobackup/apikeys/ @self:../keys")
      ->run();
  }

  /**
   * Send encryption keys to acquia.
   *
   * @param string $env
   *   Acquia environment to send the keys.
   *
   * @command humsci:keys:send
   */
  public function humsciKeysSend($env = 'prod') {
    $send = $this->confirm('Are you sure you want to copy over existing keys with keys in the "keys" directory?');
    $key_dir = $this->getConfigValue("key-dir.$env");
    if ($send) {
      $this->taskDrush()
        ->drush("rsync @self:../keys/ @default.$env:$key_dir")
        ->run();
    }
  }

  /**
   * Disables a list of modules for all sites in an environment.
   *
   * @param string $modules
   *   Comma delimited list of modules to disable.
   * @param string $environment
   *   Environment to disable modules.
   * @param string $excluded_sites
   *   Comma delimited list of sites to skip.
   *
   * @command drupal:module:uninstall
   */
  public function disableModules($modules, $environment, $excluded_sites = '') {
    if (is_string($modules)) {
      $modules = explode(',', $modules);
      array_walk($modules, 'trim');
    }
    if (is_string($excluded_sites)) {
      $excluded_sites = explode(',', $excluded_sites);
      array_walk($excluded_sites, 'trim');
    }
    foreach ($this->getConfigValue('multisites') as $multisite) {
      if (in_array($multisite, $excluded_sites)) {
        continue;
      }
      $this->taskDrush()
        ->alias("$multisite.$environment")
        ->drush('pmu')
        ->args(implode(',', $modules))
        ->drush('cr')
        ->run();
    }
  }

  /**
   * Run cron on all sites.
   *
   * @command drupal:cron
   */
  public function cron() {
    // Disable alias since we are targeting specific uri.
    $this->config->set('drush.alias', '');

    foreach ($this->getConfigValue('multisites') as $multisite) {
      try {
        $this->say("Running Cron on <comment>$multisite</comment>...");
        $this->switchSiteContext($multisite);

        $this->taskDrush()
          ->drush("cron")
          ->run();
      }
      catch (\Exception $e) {
        $this->say("Unable to run cron on <comment>$multisite</comment>");
      }
    }
  }

  /**
   * Synchronize local env from remote (remote --> local).
   *
   * Copies remote db to local db, re-imports config, and executes db updates
   * for each multisite.
   *
   * @param array $options
   *   Array of CLI options.
   *
   * @command drupal:sync:default:site
   * @aliases ds drupal:sync drupal:sync:default sync sync:refresh
   */
  public function sync(array $options = [
    'partial' => TRUE,
    'sync-public-files' => FALSE,
    'sync-private-files' => FALSE,
  ]) {
    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-public-files'] || $this->getConfigValue('sync.public-files')) {
      $commands[] = 'drupal:sync:public-files';
    }
    if ($options['sync-private-files'] || $this->getConfigValue('sync.private-files')) {
      $commands[] = 'drupal:sync:private-files';
    }
    $this->invokeCommands($commands);
  }

  /**
   * Changes necessary configuration and adds the domain to the LE Cert.
   *
   * @param string $site
   *   The machine name of the site.
   *
   * @command humsci:launch-site
   *
   * @throws \Robo\Exception\TaskException
   */
  public function launchSite($site) {
    $new_domain = preg_replace('/[^a-z]/', '-', $site);
    $new_domain = $this->askRequired('New domain?', "https://$new_domain.stanford.edu", TRUE);
    $this->switchSiteContext($site);
    $this->taskDrush()
      ->alias("$site.prod")
      ->drush('cset')
      ->arg('config_split.config_split.not_live')
      ->arg('status')
      ->arg(0)
      ->option('yes')
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('domain')
      ->arg($new_domain)
      ->option('yes')
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('enabled')
      ->arg(1)
      ->option('yes')
      ->drush('pmu')
      ->arg('nobots')
      ->drush('state:set')
      ->arg('xmlsitemap_base_url')
      ->arg($new_domain)
      ->option('yes')
      ->drush('xmlsitemap:rebuild')
      ->drush('cr')
      ->run();
  }

}
