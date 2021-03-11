<?php

namespace Humsci\Blt\Plugin\Commands;

use Symfony\Component\Console\Question\Question;

/**
 * Various BLT commands for H&S stack.
 */
class HsCommands extends HsAcquiaApiCommands {

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
   * Get encryption keys from acquia.
   *
   * @param string $env
   *   Acquia environment to send the keys.
   *
   * @command humsci:keys:send
   */
  public function humsciKeysSend($env = 'prod') {
    $send = $this->askQuestion('Are you sure you want to copy over existing keys with keys in the "keys" directory? (Y/N)', 'N', TRUE);
    $key_dir = $this->getConfigValue("key-dir.$env");
    if (strtolower($send[0]) == 'y') {
      $this->taskDrush()
        ->drush("rsync @self:../keys/ @default.$env:$key_dir")
        ->run();
    }
  }

  /**
   * Create a new database on Acquia environment.
   *
   * @command humsci:create-database
   */
  public function createDatabase() {
    $database = $this->getMachineName('What is the name of the database? This ideally will match the site directory name. No special characters please.');
    $this->connectAcquiaApi();
    $this->say('<info>' . $this->acquiaDatabases->create($this->appId, $database)->message . '</info>');
  }

  /**
   * Add a domain to Acquia environment.
   *
   * @param string $environment
   *   Environment: dev, test, or prod.
   * @param string $domains
   *   Comma separated new domain to add.
   *
   * @command humsci:add-domain
   */
  public function humsciAddDomain($environment, $domains) {
    $this->connectAcquiaApi();
    foreach (explode(',', $domains) as $domain) {
      $this->say($this->acquiaDomains->create($this->getEnvironmentUuid($environment), $domain)->message);
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
          ->drush('cr')
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
    'partial' => true,
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
    $new_domain = $this->askQuestion('New domain?', "https://$new_domain.stanford.edu", TRUE);
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

  /**
   * Sync the staging sites databases with that from production.
   *
   * @command humsci:sync-stage
   *
   * @options exclude Comma separated list of database names to skip.
   */
  public function syncStaging(array $options = ['exclude' => NULL]) {
    $task_started = time() - (60 * 60 * 24);
    $this->connectAcquiaApi();

    $sites = $this->getSitesToSync($task_started, $options);
    if (!$this->confirm(sprintf('Are you sure you wish to stage the following sites: <comment>%s</comment>', implode(', ', $sites)))) {
      return;
    }
    $count = count($sites);
    $copy_sites = array_splice($sites, 0, 4);

    foreach ($copy_sites as $site) {
      $this->say("Copying $site database to staging.");
      $this->acquiaDatabases->copy($this->getEnvironmentUuid('prod'), $site, $this->getEnvironmentUuid('test'));
    }

    while (!empty($sites)) {
      echo '.';
      sleep(30);
      $finished_databases = $this->getCompletedDatabaseCopies($task_started);

      if ($finished = array_intersect($copy_sites, $finished_databases)) {
        echo PHP_EOL;
        foreach ($finished as $copied_db) {
          $db_position = array_search($copied_db, $copy_sites);
          $new_site = array_splice($sites, 0, 1);
          $new_site = reset($new_site);
          $copy_sites[$db_position] = $new_site;
          $this->say("Copying $new_site database to staging.");
          $this->connectAcquiaApi();
          $this->say($this->acquiaDatabases->copy($this->getEnvironmentUuid('prod'), $new_site, $this->getEnvironmentUuid('test'))->message);
        }
      }
    }
    $this->yell("$count database have been copied to staging.");
  }

  /**
   * Get an overall list of database names to sync.
   *
   * @param int $task_started
   *   Time to compare the completed task.
   * @param array $options
   *   Array of keyed command options.
   *
   * @return array
   *   Array of database names to sync.
   */
  protected function getSitesToSync($task_started, array $options) {
    $finished_databases = $this->getCompletedDatabaseCopies($task_started);

    $sites = $this->getConfigValue('multisites');
    foreach ($sites as $key => &$db_name) {
      $db_name = $db_name == 'default' ? 'swshumsci' : $db_name;

      if (strpos($db_name, 'sandbox') !== FALSE) {
        unset($sites[$key]);
      }
    }
    if (!empty($options['exclude'])) {
      $exclude = explode(',', $options['exclude']);
      $sites = array_diff($sites, $exclude);
    }
    return array_diff($sites, $finished_databases);
  }

  /**
   * Get a list of all databases that have finished copying after a time.
   *
   * @param int $time_comparison
   *   Time to compare the completed task.
   *
   * @return array
   *   Array of database names.
   */
  protected function getCompletedDatabaseCopies($time_comparison) {
    $databases = [];
    $this->connectAcquiaApi();
    /** @var \AcquiaCloudApi\Response\NotificationResponse $notification */
    foreach ($this->acquiaNotifications->getAll($this->appId) as $notification) {
      if (
        isset($notification->event) &&
        $notification->event == 'DatabaseCopied' &&
        $notification->status == 'completed' &&
        strtotime($notification->created_at) >= $time_comparison
      ) {
        $databases = array_merge($databases, $notification->context->database->names);
      }
    }
    return array_values(array_unique($databases));
  }

  /**
   * Ask the user for a new stanford url and validate the entry.
   *
   * @param string $message
   *   Prompt for the user.
   *
   * @return string
   *   User entered value.
   */
  protected function getMachineName($message) {
    $question = new Question($this->formatQuestion($message));
    $question->setValidator(function ($answer) {
      $modified_answer = strtolower($answer);
      $modified_answer = preg_replace("/[^a-z0-9_]/", '_', $modified_answer);
      if ($modified_answer != $answer) {
        throw new \RuntimeException(
          'Only lower case alphanumeric characters with underscores are allowed.'
        );
      }
      return $answer;
    });
    return $this->doAsk($question);
  }

}
