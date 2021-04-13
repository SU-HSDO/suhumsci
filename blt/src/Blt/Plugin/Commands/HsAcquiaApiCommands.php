<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
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
          $this->say($this->acquiaDatabases->copy($this->getEnvironmentUuid('prod'), $new_site, $this->getEnvironmentUuid('test'))->message);
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
        else {
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
    if (!$this->acquiaApplications) {
      $this->traitConnectAcquiaApi();
    }
    try {
      $this->acquiaApplications->getAll();
    }
    catch (\Exception $e) {
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

}
