<?php

use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;

/**
 * Base tasks for setting up a module to test within a full Drupal environment.
 *
 * This file expects to be called from the root of a Drupal site.
 *
 * @class RoboFile
 * @codeCoverageIgnore
 */
class RoboFile extends Tasks {

  /**
   * The database URL.
   *
   * @var string
   */
  const DB_URL = 'mysql://root@127.0.0.1/drupal8';

  /**
   * Directory of drupal installation.
   *
   * @var string
   */
  const DRUPAL_ROOT = 'docroot';

  /**
   * Command to run unit tests.
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function jobRunUnitTests() {
    $collection = $this->collectionBuilder();
    $collection->addTask($this->installDependencies());
    $collection->addTask($this->waitForDatabase());
    $collection->addTask($this->installDrupal());
    $collection->addTaskList($this->runUnitTests());
    return $collection->run();
  }

  /**
   * Command to generate a coverage report.
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function jobGenerateCoverageReport() {
    $collection = $this->collectionBuilder();
    $collection->addTask($this->installDependencies());
    $collection->addTask($this->waitForDatabase());
    $collection->addTask($this->installDrupal());
    $collection->addTaskList($this->runUnitTestsWithCoverage());
    return $collection->run();
  }

  /**
   * Command to check for Drupal's Coding Standards.
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function jobCheckCodingStandards() {
    $collection = $this->collectionBuilder();
    $collection->addTask($this->installDependencies());
    $collection->addTaskList($this->runCodeSniffer());
    return $collection->run();
  }

  /**
   * Command to run behat tests.
   *
   * @return \Robo\Result
   *   The result tof the collection of tasks.
   */
  public function jobRunBehatTests() {
    $collection = $this->collectionBuilder();
    $collection->addTask($this->installDependencies());
    $collection->addTask($this->waitForDatabase());
    foreach ($this->getSites() as $site) {
      //      $this->yell("Testing against: $site");
      $collection->addTaskList($this->syncAcquia($site));
      $collection->addTaskList($this->runUpdatePath(TRUE));
      $collection->addTaskList($this->runBehatTests());
    }
    return $collection->run();
  }

  /**
   * Updates the database.
   *
   * @param bool $partial_config
   *   Use partial option when doing config import.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runUpdatePath($partial_config = FALSE) {
    $tasks = [];

    static $keys_loaded = FALSE;
    // Get encryption keys first and only once.
    if (!$keys_loaded) {
      $tasks[] = $this->blt()
        ->arg('humsci:keys');
      $keys_loaded = TRUE;
    }

    $tasks[] = $this->drush()
      ->args('updatedb')
      ->option('yes')
      ->option('verbose');

    $tasks[] = $this->blt()
      ->arg('drupal:toggle:modules')
      ->option('environment', 'ci', '=');

    $config_import = $this->drush()
      ->args('config-import')
      ->option('yes')
      ->option('verbose');
    if ($partial_config) {
      $config_import->option('partial');
    }

    $tasks[] = $config_import;
    return $tasks;
  }

  /**
   * Runs Behat tests.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runBehatTests() {
    $tasks = [];
    // Don't use blt to run behat here. It's dependencies conflict with
    // circleci too much.
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/behat.yml', 'tests/behat/behat.yml', TRUE);
    $tasks[] = $this->taskExec('vendor/bin/behat --verbose -c tests/behat/behat.yml');
    return $tasks;
  }

  /**
   * Installs composer dependencies.
   *
   * @return \Robo\Contract\TaskInterface
   *   A task instance.
   */
  protected function installDependencies() {
    return $this->taskComposerInstall()
      ->optimizeAutoloader();
  }

  /**
   * Waits for the database service to be ready.
   *
   * @return \Robo\Contract\TaskInterface
   *   A task instance.
   */
  protected function waitForDatabase() {
    return $this->taskExec('dockerize -wait tcp://localhost:3306 -timeout 1m');
  }

  /**
   * Install Drupal.
   *
   * @param string $profile
   *   Which install profile to use.
   *
   * @return \Robo\Task\Base\Exec
   *   A task to install Drupal.
   */
  protected function installDrupal($profile = 'minimal') {
    $task = $this->drush()
      ->args('site-install')
      ->args($profile)
      ->option('verbose')
      ->option('yes')
      ->option('db-url', static::DB_URL, '=');
    return $task;
  }

  /**
   * Syncs the site to Acquia.
   *
   * We can't use drush sql-sync because it causes rsync issues when it tries
   * to chown the files.
   *
   * @param string $site
   *   Machine name of the site to syn.
   *
   * @return \Robo\Task\Base\Exec[]
   *   Array of tasks.
   */
  protected function syncAcquia($site = 'swshumsci') {
    $tasks = [];
    $tasks[] = $this->taskExec('mysql -u root -h 127.0.0.1 -e "create database IF NOT EXISTS drupal8"');
    // Copy site specific settings files to default settings.
    if ($site != 'default' && $site != 'swshusmci') {
      $tasks[] = $this->taskFilesystemStack()
        ->copy(static::DRUPAL_ROOT . "/sites/$site/settings.php", static::DRUPAL_ROOT . "/sites/default/settings.php", TRUE);
    }
    // Copy database credentials to be included via BLT.
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/circleci.settings.php', static::DRUPAL_ROOT . '/sites/default/settings/includes.settings.php', TRUE);

    // This line is just to test connection and to prevent unwanted line at
    // the beginning of the db dump. Without this, we would get the text
    // "Warning: Permanently added the RSA host key for IP address" at the top
    // of the db dump.
    $tasks[] = $this->drush()->rawArg("@$site.prod sql-connect");
    $tasks[] = $this->drush()->rawArg("@$site.prod sql-dump > dump.sql");

    // At the end of the drush command, we need to remove the ssh command.
    $tasks[] = $this->taskExecStack()
      ->exec("grep -v '^Connection to' dump.sql > clean_dump.sql");
    $tasks[] = $this->drush()
      ->rawArg('@self sql-cli < clean_dump.sql');
    return $tasks;
  }

  /**
   * Run unit tests.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runUnitTests() {
    $force = TRUE;
    $tasks = [];
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/phpunit.xml', static::DRUPAL_ROOT . '/core/phpunit.xml', $force)
      ->mkdir('../artifacts/phpunit', 777);

    $tasks[] = $this->taskExecStack()
      ->dir(static::DRUPAL_ROOT)
      ->exec('../vendor/bin/phpunit -c core --debug --verbose --log-junit ../artifacts/phpunit/phpunit.xml modules/humsci');
    return $tasks;
  }

  /**
   * Run unit tests and generates a code coverage report.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runUnitTestsWithCoverage() {
    $force = TRUE;
    $tasks = [];
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/phpunit.xml', static::DRUPAL_ROOT . '/core/phpunit.xml', $force)
      ->mkdir('artifacts/coverage-xml', 777)
      ->mkdir('artifacts/coverage-html', 777);
    $tasks[] = $this->taskExecStack()
      ->dir(static::DRUPAL_ROOT)
      ->exec('../vendor/bin/phpunit -c core --debug --verbose --coverage-xml ../artifacts/coverage-xml --coverage-html ../artifacts/coverage-html modules/humsci');
    return $tasks;
  }

  /**
   * Sets up and runs code sniffer.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runCodeSniffer() {
    $tasks = [];
    $tasks[] = $this->taskExecStack()
      ->exec('vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer');
    $tasks[] = $this->taskFilesystemStack()
      ->mkdir('artifacts/phpcs');
    $tasks[] = $this->taskExecStack()
      ->exec('vendor/bin/phpcs --standard=Drupal --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . static::DRUPAL_ROOT . '/modules/humsci')
      ->exec('vendor/bin/phpcs --standard=DrupalPractice --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . static::DRUPAL_ROOT . '/modules/humsci');
    return $tasks;
  }

  /**
   * Return drush with default arguments.
   *
   * @return \Robo\Task\Base\Exec
   *   A drush exec command.
   */
  protected function drush() {
    // Drush needs an absolute path to the docroot.
    $docroot = $this->getDocroot() . '/' . static::DRUPAL_ROOT;
    return $this->taskExec('vendor/bin/drush')
      ->option('root', $docroot, '=');
  }

  /**
   * Get the absolute path to the docroot.
   *
   * @return string
   *   The repo directory.
   */
  protected function getDocroot() {
    $docroot = (getcwd());
    return $docroot;
  }

  /**
   * Return BLT.
   *
   * @return \Robo\Task\Base\Exec
   *   A drush exec command.
   */
  protected function blt() {
    return $this->taskExec('vendor/acquia/blt/bin/blt')
      ->option('verbose');
  }

  /**
   * Get all available sites in multisite setup.
   *
   * @return array
   *   Array of machine names for sites.
   */
  protected function getSites() {
    $blt_config = Yaml::parseFile($this->getDocroot() . '/blt/blt.yml');
    return $blt_config['multisites'];
  }

}
