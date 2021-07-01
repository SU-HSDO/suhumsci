<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;

/**
 * ACP -> ACE migration commands.
 */
class HsAceMigrationCommands extends BltTasks {

  /**
   * Download and upload the database from ACP Prod -> ACE Prod.
   *
   * @command move-sites:acp
   */
  public function moveAllAcpSites($options = ['with-files' => FALSE]) {
    $still_on_acp = [];
    foreach ($this->getConfigValue('multisites') as $site) {
      $site_url = str_replace('_', '-', str_replace('__', '.', $site));
      $url = "https://$site_url.stanford.edu";
      $ip_address = gethostbyname(parse_url($url, PHP_URL_HOST));
      if (!(int) $ip_address) {
        $url = "https://$site_url-prod.stanford.edu";
        $ip_address = gethostbyname(parse_url($url, PHP_URL_HOST));
      }
      $this->say("$site: $ip_address");
      if ($ip_address == '52.7.108.255') {
        $still_on_acp[] = $site;
      }
    }
    $confirm = $this->confirm('Do you want to drop all data on the ACE Prod and replace it with ACP prod for the given sites: ' . implode(', ', $still_on_acp));
    if ($confirm) {
      $this->invokeCommand('move-sites:db', ['sites' => implode(',', $still_on_acp)]);
      if ($options['with-files']) {
        $this->invokeCommand('move-sites:files', ['sites' => implode(',', $still_on_acp)]);
      }
    }
  }

  /**
   * Download and upload the database from ACP Prod -> ACE Prod.
   *
   * @command move-sites:db
   */
  public function moveSites($sites) {

    $docroot = $this->getConfigValue('docroot');
    foreach (explode(',', $sites) as $site) {
      $this->taskDrush()
        ->alias("$site.acp")
        ->drush('cache:rebuild')
        ->drush('sql-dump')
        ->rawArg("> $docroot/$site.sql")
        ->run();
      $this->taskDrush()
        ->alias("$site.prod")
        ->drush('sql-drop')
        ->drush('sql-cli')
        ->rawArg("< $docroot/$site.sql")
        ->drush('cache:rebuild')
        ->drush('updb')
        ->drush('cim')
        ->option('partial')
        ->run();
    }

  }

  /**
   * Rsync the site files from ACP Prod -> ACE Prod.
   *
   * @command move-sites:files
   */
  public function moveSiteFiles($sites, $options = ['include-image-styles' => FALSE]) {
    $docroot = $this->getConfigValue('docroot');

    foreach (explode(',', $sites) as $site) {
      $this->taskDrush()
        ->drush('rsync')
        ->arg("@$site.acp:%files/")
        ->arg("$docroot/sites/$site/files")
        ->option('exclude-paths', $options['include-image-styles'] ? 'css:js:php' : 'styles:css:js:php')
        ->option('debug')
        ->run();
      $this->taskDrush()
        ->drush('rsync')
        ->arg("$docroot/sites/$site/files/")
        ->arg("@$site.prod:%files")
        ->option('debug')
        ->option('update')
        ->run();
    }
  }

}
