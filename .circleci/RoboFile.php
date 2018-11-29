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
 * @see https://github.com/lullabot/drupal8ci/
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
   * Directory to run unit tests, relative to drupal root.
   *
   * @var string
   */
  const TEST_DIR = 'modules/humsci';

  /**
   * Config from blt.yml file.
   *
   * @var array
   */
  protected $bltConfig;

  /**
   * Command to run unit tests.
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function jobRunUnitTests() {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
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
    $collection->addTaskList($this->setupSite());
    $collection->addTask($this->installDrupal());
    $collection->addTaskList($this->runUnitTestsWithCoverage());
    return $collection->run();
  }

  /**
   * Command to run behat tests.
   *
   * @return \Robo\Result
   *   The result tof the collection of tasks.
   */
  public function jobRunBehatTestsFirst() {
    $all_sites = $this->getSites();
    $sites = array_slice($all_sites, 0, count($all_sites) / 2);
    return $this->runBehatTest($sites);
  }

  /**
   * Command to run behat tests.
   *
   * @return \Robo\Result
   *   The result tof the collection of tasks.
   */
  public function jobRunBehatTestsSecond() {
    $all_sites = $this->getSites();
    $sites = array_slice($all_sites, count($all_sites) / 2);
    return $this->runBehatTest($sites);
  }

  /**
   * Run behat tests on the given sites.
   *
   * @param array $sites
   *   Array of site machine names.
   *
   * @return \Robo\Result
   *   Tasks collection.
   */
  protected function runBehatTest(array $sites) {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
    foreach ($sites as $site) {
      $collection->addTaskList($this->syncAcquia($site));
      $collection->addTaskList($this->runUpdatePath(TRUE));
      $collection->addTaskList($this->runBehatTests(['global', $site]));
    }
    return $collection->run();
  }

  /**
   * Perform some tasks to prepare the drupal environment.
   *
   * @return \Robo\Contract\TaskInterface[]
   *   List of tasks to set up site.
   */
  protected function setupSite() {
    $tasks[] = $this->installDependencies();
    $tasks[] = $this->waitForDatabase();
    $tasks[] = $this->taskExec('service apache2 start');
    return $tasks;
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

    // Get encryption keys first and only once.
    static $keys_loaded = FALSE;
    if (!$keys_loaded) {
      $tasks[] = $this->blt()->arg('humsci:keys');
      $keys_loaded = TRUE;
    }

    // Update the database.
    $tasks[] = $this->drush()->args('updatedb')
      ->option('yes')
      ->option('verbose');

    // Toggle modules for CI environment. Modules should match production.
    $tasks[] = $this->blt()->arg('drupal:toggle:modules')
      ->option('environment', 'ci', '=');

    // Import the configs.
    $config_import = $this->drush()->args('config-import')
      ->option('yes')
      ->option('verbose');
    if ($partial_config) {
      $config_import->option('partial');
    }

    $tasks[] = $config_import;
    $tasks[] = $this->drush()->arg('cron');
    return $tasks;
  }

  /**
   * Runs Behat tests.
   *
   * @param string[] $tags
   *   Array of tags to run tests.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runBehatTests(array $tags = ['global']) {
    $tasks = [];
    // Don't use blt to run behat here. It's dependencies conflict with
    // circleci too much.
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/behat.yml', 'tests/behat/behat.yml', TRUE);
    $tasks[] = $this->taskExec('vendor/bin/behat --verbose -c tests/behat/behat.yml --tags=' . implode(',', $tags));
    return $tasks;
  }

  /**
   * Installs composer dependencies.
   *
   * @return \Robo\Contract\TaskInterface
   *   A task instance.
   */
  protected function installDependencies() {
    return $this->taskComposerInstall()->optimizeAutoloader();
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

    // To make things easy on setting up these tests, we'll just use the default
    // directory for all site tests. But that means we need to bring over the
    // site specific settings.php files too account for anything specific.
    if ($site != 'default' && $site != 'swshusmci') {
      $tasks[] = $this->taskFilesystemStack()
        ->copy(static::DRUPAL_ROOT . "/sites/$site/settings.php", static::DRUPAL_ROOT . "/sites/default/settings.php", TRUE);
    }

    // Copy circle.ci settings. This setting is included from blt.
    // @see https://github.com/acquia/blt/blob/9.x/settings/blt.settings.php#L284
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/circleci.settings.php', static::DRUPAL_ROOT . '/sites/default/settings/includes.settings.php', TRUE);

    // This line is just to test connection and to prevent unwanted line at
    // the beginning of the db dump. Without this, we would get the text
    // "Warning: Permanently added the RSA host key for IP address" at the top
    // of the db dump.
    $tasks[] = $this->drush()->rawArg("@$site.prod sql-connect");
    $tasks[] = $this->drush()->rawArg("@$site.prod sql-dump > dump.sql");

    // At the end of the drush command, we need to remove the ssh connection
    // closed message.
    $tasks[] = $this->taskExecStack()
      ->exec("grep -v '^Connection to' dump.sql > clean_dump.sql");

    $tasks[] = $this->drush()->arg('sql-drop')->option('yes');
    $tasks[] = $this->drush()
      ->rawArg('@self sql-cli < clean_dump.sql');
    return $tasks;
  }

  /**
   * Run Functional unit tests since non-functional are tested with coverage.
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

    $tasks[] = $this->taskExecStack()->dir(static::DRUPAL_ROOT)
      ->exec('../vendor/bin/phpunit -c core --log-junit ../artifacts/phpunit/phpunit.xml --testsuite functional ' . static::TEST_DIR);
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
    $tasks[] = $this->taskExecStack()->dir(static::DRUPAL_ROOT)
      ->exec('../vendor/bin/phpunit -c core --debug --verbose --coverage-xml ../artifacts/coverage-xml --coverage-html ../artifacts/coverage-html --testsuite nonfunctional ' . static::TEST_DIR);
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
    $tasks[] = $this->taskFilesystemStack()->mkdir('artifacts/phpcs');
    $tasks[] = $this->taskExecStack()
      ->exec('vendor/bin/phpcs --standard=Drupal --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . static::DRUPAL_ROOT . '/' . static::TEST_DIR)
      ->exec('vendor/bin/phpcs --standard=DrupalPractice --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . static::DRUPAL_ROOT . '/' . static::TEST_DIR);
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
    $blt_config = $this->getBltConfig();
    $sites = [];
    foreach ($blt_config['multisites'] as $site) {
      // Sandbox sites are unpredictable, so lets ignore them.
      if (strpos($site, 'sandbox') === FALSE) {
        $sites[] = $site;
      }
    }
    return $sites;
  }

  /**
   * Get BLT Config settings from blt.yml file.
   *
   * @return array
   *   BLT Settings.
   */
  protected function getBltConfig() {
    if (!isset($this->bltConfig)) {
      $this->bltConfig = Yaml::parseFile($this->getDocroot() . '/blt/blt.yml');
    }
    return $this->bltConfig;
  }

}
