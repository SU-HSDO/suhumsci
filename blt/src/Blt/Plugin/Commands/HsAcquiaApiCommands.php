<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;
use Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait;
use Symfony\Component\Console\Question\Question;

if (!trait_exists('Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait')) {
  return;
}

/**
 * Class HsAcquiaApiCommands.
 *
 * @package Humsci\Blt\Plugin\Commands
 */
class HsAcquiaApiCommands extends BltTasks {

  use SwsCommandTrait {
    connectAcquiaApi as traitConnectAcquiaApi;
  }

  /**
   * List of failed databases from the sync command.
   *
   * @var array
   */
  protected $failedDatabases = [];

  /**
   * Get the environment UUID for the application from the machine name.
   *
   * @param string $name
   *   Environment machine name.
   *
   * @return string
   *   Environment UUID.
   *
   * @throws \Exception
   */
  protected function getEnvironmentUuid(string $name) {
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $env */
    foreach ($this->acquiaEnvironments->getAll($this->appId) as $env) {
      if ($env->name == $name) {
        return $env->uuid;
      }
    }
    throw new \Exception(sprintf('Unable to find environment name %s', $name));
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
   * Create backups of all databases on the production environment.
   *
   * @command humsci:backup-db
   * @aliases backup
   */
  public function backupDatabases($options = ['start-at' => NULL]) {
    $this->connectAcquiaApi();
    $skip = TRUE;
    foreach ($this->acquiaDatabases->getAll($this->appId) as $database) {
      if (!empty($options['start-at']) && $skip) {
        if ($options['start-at'] == $database->name) {
          $skip = FALSE;
        }
        continue;
      }
      $message = $this->acquiaDatabaseBackups->create($this->getEnvironmentUuid('prod'), $database->name)->message;
      $this->say(sprintf('%s: %s', $database->name, $message));
      sleep(5);
    }
  }

  /**
   * Delete all old database backups.
   *
   * @command humsci:clean-backups
   */
  public function deleteOldBackups() {
    $this->connectAcquiaApi();
    $environments = $this->acquiaEnvironments->getAll($this->appId);
    $environment_uuids = [];

    foreach ($environments as $environment) {
      if ($environment->name != 'ra') {
        $environment_uuids[$environment->uuid] = $environment->name;
      }
    }

    foreach ($this->acquiaDatabases->getAll($this->appId) as $database) {
      $this->say(sprintf('Gather database backup info for %s', $database->name));

      foreach ($environment_uuids as $environment_uuid => $name) {
        $backups = $this->acquiaDatabaseBackups->getAll($environment_uuid, $database->name);
        foreach ($backups as $backup) {

          $start_at = strtotime($backup->startedAt);
          if ($backup->type == 'ondemand' && time() - $start_at > 60 * 60 * 24 * 7) {
            $this->say(sprintf('Deleting %s backup #%s on %s environment.', $database->name, $backup->id, $name));
            $this->acquiaDatabaseBackups->delete($environment_uuid, $database->name, $backup->id);
          }
        }
      }
    }
  }

  /**
   * Sync the staging sites databases with that from production.
   *
   * @command humsci:sync-stage
   * @aliases stage
   *
   * @options exclude Comma separated list of database names to skip.
   */
  public function syncStaging(array $options = [
    'exclude' => NULL,
    'resume' => FALSE,
    'env' => 'test',
  ]) {
    $task_started = time() - (60 * 60 * 24);
    $this->connectAcquiaApi();

    $sites = $this->getSitesToSync($task_started, $options);
    if (empty($options['no-interaction']) && !$this->confirm(sprintf('Are you sure you wish to stage the following sites: <comment>%s</comment>', implode(', ', $sites)))) {
      return;
    }
    $count = count($sites);
    $copy_sites = array_splice($sites, 0, 4);

    foreach ($copy_sites as $site) {
      $this->say("Copying $site database to staging.");
      $this->acquiaDatabases->copy($this->getEnvironmentUuid('prod'), $site, $this->getEnvironmentUuid($options['env']));
    }

    while (!empty($sites)) {
      echo '.';
      sleep(10);
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
          $this->say($this->acquiaDatabases->copy($this->getEnvironmentUuid('prod'), $new_site, $this->getEnvironmentUuid($options['env']))->message);
        }
      }
    }
    $this->yell("$count database have been copied to staging.");

    if (array_unique($this->failedDatabases)) {
      $this->yell('Databases failed: ' . implode(', ', array_unique($this->failedDatabases)), 40, 'red');
    }
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
        strtotime($notification->created_at) >= $time_comparison
      ) {
        if ($notification->status == 'completed') {
          $databases = array_merge($databases, $notification->context->database->names);
        }
        elseif ($notification->status != 'in-progress') {
          $this->failedDatabases = array_merge($this->failedDatabases, $notification->context->database->names);
        }
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

  /**
   * {@inheritDoc}
   */
  protected function connectAcquiaApi() {
    $acquia_conf = $_SERVER['HOME'] . '/.acquia/cloud_api.conf';
    $key = getenv('ACQUIA_KEY');
    $secret = getenv('ACQUIA_SECRET');
    if ($key && $secret && !file_exists($acquia_conf)) {
      mkdir(dirname($acquia_conf), 0777, TRUE);
      $conf = ['key' => $key, 'secret' => $secret];
      file_put_contents($acquia_conf, json_encode($conf, JSON_PRETTY_PRINT));
    }

    if (!$this->acquiaApplications) {
      $this->traitConnectAcquiaApi();
    }
    try {
      $this->acquiaApplications->getAll();
    } catch (\Throwable $e) {
      $this->traitConnectAcquiaApi();
    }
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
      $db_name = $db_name == 'default' ? 'humscigryphon' : $db_name;

      if (strpos($db_name, 'sandbox') !== FALSE) {
        unset($sites[$key]);
      }
    }
    $sites = array_values($sites);
    if (!empty($options['exclude'])) {
      $exclude = explode(',', $options['exclude']);
      $sites = array_diff($sites, $exclude);
    }

    if ($options['resume']) {
      asort($finished_databases);
      $last_database = end($finished_databases);
      $last_db_position = array_search($last_database, $sites);
      $sites = array_slice($sites, $last_db_position);
    }
    return array_diff($sites, $finished_databases);
  }

  /**
   * Copy hs_colorful site database and files to hs_traditional stage and prod.
   *
   * @command humsci:copy-colorful
   */
  public function copyHsColorful() {
    $database_path = sys_get_temp_dir() . '/temp.hs_colorful.sql';
    $docroot = $this->getConfigValue('docroot');
    $tasks[] = $this->taskDrush()
      ->alias('hs_colorful.prod')
      ->drush('sql-dump')
      ->rawArg("> $database_path")
      ->rawArg('-Dssh.tty=0');
    $tasks[] = $this->taskDrush()
      ->drush('rsync')
      ->rawArg('@hs_colorful.prod:%files/')
      ->rawArg("$docroot/sites/hs_colorful/files")
      ->option('exclude-paths', 'css:js')
      ->option('no-interaction')
      ->option(' --delete')
      ->interactive(TRUE)
      ->ansi(FALSE);
    $destinations = [
      'hs_colorful.stage',
      'hs_traditional.stage',
      'hs_traditional.prod',
    ];
    foreach ($destinations as $destination) {
      $tasks[] = $this->taskDrush()
        ->alias($destination)
        ->drush('sql-drop')
        ->drush('sql-cli')
        ->rawArg("< $database_path")
        ->drush('cr');

      $tasks[] = $this->taskDrush()
        ->drush('rsync')
        ->rawArg("$docroot/sites/hs_colorful/files/")
        ->rawArg("@$destination:%files/")
        ->option('no-interaction')
        ->option(' --delete')
        ->interactive(TRUE)
        ->ansi(FALSE);
    }
    return $this->collectionBuilder()->addTaskList($tasks)->run();
  }

}
