<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;
use Acquia\Blt\Robo\Exceptions\BltException;

/**
 * Defines commands in the "custom" namespace.
 */
class HumsciCommand extends AcHooksCommand {

  /**
   * Synchronize local env from remote (remote --> local).
   *
   * Copies remote db to local db, re-imports config, and executes db updates
   * for each multisite.
   *
   * @command drupal:sync:default:site
   * @aliases ds drupal:sync drupal:sync:default sync sync:refresh
   * @executeInVm
   */
  public function sync($options = ['sync-files' => FALSE]) {

    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-files'] || $this->getConfigValue('sync.files')) {
      $commands[] = 'drupal:sync:files';
    }
    $this->invokeCommands($commands);

    return $this->taskDrush()->drush('updb -y')->run();
  }

  /**
   * Set up local environment.
   *
   * @command local:setup
   */
  public function localSetup() {
    $this->invokeCommand('setup:settings');

    $multisites = $this->getConfigValue('multisites');
    $initial_site = $this->getConfigValue('site');
    $current_site = $initial_site;

    foreach ($multisites as $multisite) {
      if ($current_site != $multisite) {
        $this->switchSiteContext($multisite);
        $current_site = $multisite;
      }
      $status = $this->getInspector()->getStatus();

      // Generate settings.php.
      $multisite_dir = $this->getConfigValue('docroot') . "/sites/$multisite";
      $project_local_settings_file = "$multisite_dir/settings/local.settings.php";
      $settings_contents = file_get_contents($project_local_settings_file);

      $database_name = $this->getDatabaseName($multisite, $status['db-name']);

      $database_host = $this->askQuestion('Database Host', $status['db-hostname'], TRUE);
      $database_port = $this->askQuestion('Database Port', $status['db-port']);

      if ($multisite == 'default') {
        $database_user_name = $this->askQuestion('Database user name?', $status['db-username'], TRUE);
        $database_password = $this->askQuestion('Database password?', $status['db-password'], TRUE);
      }
      else {
        $database_user_name = $this->askQuestion("Database user name for $multisite site?", $status['db-username'], TRUE);
        $database_password = $this->askQuestion("Database password for $multisite site?", $status['db-password'], TRUE);
      }

      $settings_contents = preg_replace("/db_name = .*?;/", "db_name = '$database_name';", $settings_contents);
      $settings_contents = preg_replace("/'username' => '.*?',/", "'username' => '$database_user_name',", $settings_contents);
      $settings_contents = preg_replace("/'password' => '.*?',/", "'password' => '$database_password',", $settings_contents);
      $settings_contents = preg_replace("/'host' => '.*?',/", "'host' => '$database_host',", $settings_contents);
      $settings_contents = preg_replace("/'port' => '.*?',/", "'port' => '$database_port',", $settings_contents);

      file_put_contents($project_local_settings_file, $settings_contents);

      $status = $this->getInspector()->getStatus();
      $connection = @mysqli_connect(
        $status['db-hostname'],
        $status['db-username'],
        $status['db-password'],
        '',
        $status['db-port']
      );

      if (!$connection) {
        throw new BltException("Unable to connect to database.");
      }
      $connection->query('CREATE DATABASE IF NOT EXISTS ' . $status['db-name']);
    }
  }

  /**
   * Set up local Lando environment.
   *
   * @command local:setup:lando
   */
  public function localLandoSetup() {
    return $this->getConfigValue('multisites');
  }

  /**
   * @param $question
   * @param string $default
   * @param bool $required
   *
   * @return string
   */
  protected function askQuestion($question, $default = '', $required = FALSE) {
    if ($default) {
      $response = $this->askDefault($question, $default);
    }
    else {
      $response = $this->ask($question);
    }
    if ($required && !$response) {
      return $this->askQuestion($question, $default, $required);
    }
    return $response;
  }

  /**
   * @param string $multisite
   *
   * @return string
   */
  protected function getDatabaseName($multisite = 'default', $default = 'drupal') {
    $database_name = '';
    $count = 0;
    while (!preg_match("/^[a-z0-9_]+$/", $database_name)) {

      if (!$count) {
        $this->say('<info>Only lower case alphanumeric characters and underscores are allowed in the database name.</info>');
      }
      $question = "Database name for $multisite site?";
      if ($multisite == 'default') {
        $question = 'Database name?';
      }
      $database_name = $this->askDefault($question, $default);
      $count++;
    }
    return $database_name;
  }

  /**
   * Copies remote db to local db for default site.
   *
   * @param string $environment
   *   The environment as defined in project.yml or project.local.yml.
   *
   * @return object
   *   The Robo/Result object.
   *
   * @command drupal:sync:default:db
   *
   * @aliases dsb drupal:sync:db sync:db
   */
  public function syncDbDefault($environment = 'remote') {
    $local_alias = '@' . $this->getConfigValue('drush.aliases.local');
    $remote_alias = $this->getRemoteAlias($environment);

    $task = $this->taskDrush()
      ->alias('')
      ->drush('cache-clear drush')
      ->drush('sql-drop')
      ->drush('sql-sync')
      ->arg("@$remote_alias")
      ->arg($local_alias)
      // @see https://github.com/drush-ops/drush/releases/tag/9.2.1
      // @see https://github.com/acquia/blt/issues/2641
      ->option('--source-dump', sys_get_temp_dir() . '/tmp.sql')
      ->option('structure-tables-key', 'lightweight')
      ->option('create-db');

    if ($this->getConfigValue('drush.sanitize')) {
      $task->drush('sql-sanitize');
    }

    $task->drush('cr');
    $task->drush('sqlq "TRUNCATE cache_entity"');

    $result = $task->run();

    return $result;
  }

  /**
   * Overrides blt sync files command.
   *
   * @param string $environment
   *   The environment as defined in project.yml or project.local.yml.
   *
   * @return object
   *   The Robo/Result object.
   *
   * @command sync:files
   * @description Copies remote files to local machine.
   */
  public function syncFiles($environment = 'remote') {
    $remote_alias = $this->getRemoteAlias($environment);
    $site_dir = $this->getConfigValue('site');

    $task = $this->taskDrush()
      ->alias('')
      ->uri('')
      ->drush('rsync')
      ->arg("@$remote_alias" . ':%files/')
      ->arg($this->getConfigValue('docroot') . "/sites/$site_dir/files")
      ->option('exclude-paths', implode(':', $this->getConfigValue('sync.exclude-paths')));

    $result = $task->run();

    return $result;
  }

  /**
   * Get the remote alias.
   *
   * @param string $environment
   *   Environment name defined in project.yml or project.local.yml.
   *
   * @return string
   *   Drush alias name.
   */
  protected function getRemoteAlias($environment = 'remote') {

    // For ODE environments, just get the remote and replace with the ode name.
    if (strpos($environment, 'ode') !== FALSE) {
      $alias = $this->getConfigValue('drush.aliases.remote');
      return str_replace('.test', ".$environment", $alias);
    }

    return $this->getConfigValue("drush.aliases.$environment");
  }

  /**
   * Update the database to reflect the state of the Drupal file system.
   *
   * @command artifact:update:drupal:all-sites
   * @aliases auda
   */
  public function updateAll() {
    // Disable alias since we are targeting specific uri.
    $this->config->set('drush.alias', '');

    foreach ($this->getConfigValue('multisites') as $multisite) {
      try {
        $this->updateSite($multisite);
      }
      catch (\Exception $e) {
        $this->say("Unable to update <comment>$multisite</comment>");
      }
    }
  }

  /**
   * Execute updates on a specific site.
   *
   * @param string $multisite
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function updateSite($multisite) {
    $this->say("Deploying updates to <comment>$multisite</comment>...");
    $this->switchSiteContext($multisite);

    $this->invokeCommand('drupal:toggle:modules');
    $this->taskDrush()
      ->drush("updb -y")
      ->run();
    $this->taskDrush()
      ->drush("cr")
      ->run();
    $this->say("Finished deploying updates to $multisite.");
  }

}
