<?php

namespace Example\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;
use Exception;
use Robo\Contract\VerbosityThresholdInterface;
use Symfony\Component\Finder\Finder;

/**
 * Defines commands in the "humsci" namespace.
 */
class CircleCiCommands extends BltTasks {

  use HumsciTrait;

  /**
   * The database URL.
   *
   * @var string
   */
  const DB_URL = 'mysql://root@127.0.0.1/drupal8';

  /**
   * Number of random sites to test behat.
   *
   * @var integer
   */
  const SITES_TO_TEST = 6;

  /**
   * Directory to run unit tests, relative to drupal root.
   *
   * @var string
   */
  const TEST_DIR = 'modules/humsci';

  /**
   * Update all dependencies and re-export the configuration.
   *
   * @command circleci:update
   */
  public function updateDependencies() {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
    $collection->addTask($this->installDrupal('config_installer'));

    $collection->addTask($this->taskDrush()
      ->drush('config-import')
      ->option('yes'));
    $collection->addTask($this->taskComposerUpdate());

    $collection->addTask($this->taskDrush()->drush('updb')->option('yes'));
    $collection->addTask($this->taskDrush()
      ->drush('config-split:export')
      ->option('yes'));

    $collection->addTaskList($this->runBehatTests(['global', 'install']));

    $collection->addTask($this->taskGitStack()
      ->checkout($_ENV['CIRCLE_BRANCH'])
      ->add('composer.lock config')
      ->commit('Updated dependencies ' . date('M j Y'))
      ->push('origin', $_ENV['CIRCLE_BRANCH']));

    return $collection->run();
  }

  /**
   * Perform a release in github.
   *
   * @command circleci:github:release
   */
  public function jobGithubRelease() {

    $last_version = $this->getLastVersion();
    // Increment the last version by 1.
    $version = $this->incrementVersion($last_version);
    $this->yell("Releasing $version");

    // Get a list of all commits since the last version until now.
    exec("git log --pretty=format:%h $last_version...HEAD", $commit_hashes);

    $changes = [];
    // Build an array of change strings.
    foreach ($commit_hashes as $hash) {
      exec("git log --format=%B -n 1 $hash", $log);
      $log = is_array($log) ? reset($log) : $log;

      // Don't record last release commit.
      if ($log == "Release $last_version") {
        continue;
      }

      $changes[] = "$log ($hash)";
    }

    if (empty($changes)) {
      $this->say('No Changes to release.');
      return;
    }

    // Set module and profile version. Then update the changelog.
    $this->setVersions($version);
    $this->updateChangelog($version, $changes);

    // Commit all the changes and push to github.
    $result = $this->taskGitStack()
      ->add('-A')
      ->commit("Release $version")
      ->pull()
      ->push()
      ->run();

    if (!$result->wasSuccessful()) {
      throw new Exception('Release commit was unsuccessful');
    }

    $github_info = $this->getGitHubInfo();
    // Create a new release in github. This will generate a tag which will be
    // used in another CircleCI task.
    $result = $this->taskGitHubRelease($version)
      ->accessToken(getenv('GITHUB_TOKEN'))
      ->uri($github_info['owner'] . '/' . $github_info['name'])
      ->description("Release $version\n")
      ->changes($changes)
      ->name($version)
      ->comittish(getenv('CIRCLE_BRANCH'))
      ->run();

    if (!$result->wasSuccessful()) {
      throw new Exception('Release was unsuccessful');
    }

    $new_branch = $this->incrementVersion($version) . "-release";
    // Create the new release branch in github.
    $this->taskGitStack()
      ->checkout("-b $new_branch")
      ->push('origin', $new_branch)
      ->run();
    // Deploy that release to Acquia.
    $this->blt()->arg('artifact:deploy')->option('no-interaction')->run();
    sleep(10);
    $api = new AcquiaApi($this->getConfigValue('cloud'));
    $this->say($api->deployCode('test', "$new_branch-build"));
  }

  /**
   * Update the changelog file with the given changes.
   *
   * @param string $version
   *   New version.
   * @param array $changes
   *   Array of change strings.
   */
  protected function updateChangelog($version, array $changes = []) {
    array_walk($changes, function (&$change) {
      $change = '* ' . $change;
    });
    $divider = str_repeat('-', 80);
    $this->taskChangelog($this->getConfigValue('repo.root') . '/docs/CHANGELOG.md')
      ->setHeader("$version\n{$divider}\n_Release Date: " . date("Y-m-d") . "_\n\n")
      ->anchor("# HumSci")
      ->setBody(implode("\n", $changes))
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();
  }

  /**
   * Set the versions in the module & profile info files.
   *
   * @param string $version
   *   New version.
   */
  protected function setVersions($version) {
    // Update only modules in humsci and the appropriate profiles.
    $dirs = [
      '*/modules/humsci/*',
      '*/profiles/humsci/su_*',
    ];

    foreach ($dirs as $dir) {
      $modules = Finder::create()
        ->files()
        ->name('*.info.yml')
        ->in($dir);

      /** @var \Symfony\Component\Finder\SplFileInfo $module */
      foreach ($modules as $module) {
        // Don't need to update test modules. These inherit their versions.
        if (strpos($module->getPath(), 'tests') !== FALSE) {
          continue;
        }
        $info_file = Yaml::decode(file_get_contents($module->getRealPath()));
        $info_file['version'] = $version;
        file_put_contents($module->getRealPath(), Yaml::encode($info_file));
      }
    }
  }

  /**
   * Command to run unit tests.
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   *
   * @command circleci:phpunit
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
   *
   * @command circleci:phpunit:coverage
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
   *
   * @command circleci:behat:first
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
   *
   * @command circleci:behat:second
   */
  public function jobRunBehatTestsSecond() {
    $all_sites = $this->getSites();
    $sites = array_slice($all_sites, count($all_sites) / 2);
    return $this->runBehatTest($sites);
  }

  /**
   * Command to run behat tests.
   *
   * @return \Robo\Result
   *   The result tof the collection of tasks.
   *
   * @command circleci:behat:install
   */
  public function jobRunFreshInstallBehat() {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
    $collection->addTask($this->installDrupal('config_installer'));

    $collection->addTask($this->taskDrush()
      ->drush('config-import')
      ->option('yes'));
    $collection->addTaskList($this->runBehatTests(['global', 'install']));
    return $collection->run();
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

      if ($site == 'mrc') {
        // MRC is special and needs to be tested more specific.
        $collection->addTaskList($this->runBehatTests([$site]));
      }
      else {
        $collection->addTaskList($this->runBehatTests(['global', $site]));
      }
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
    $tasks[] = $this->waitForDatabase();
    $tasks[] = $this->taskExec('apachectl stop; apachectl start');
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
    $tasks[] = $this->taskDrush()
      ->drush('updatedb')
      ->option('yes')
      ->option('verbose');

    // Toggle modules for CI environment. Modules should match production.
    $tasks[] = $this->blt()->arg('drupal:toggle:modules')
      ->option('environment', 'ci', '=');

    // Import the configs.
    $config_import = $this->taskDrush()
      ->drush('config-import')
      ->option('yes')
      ->option('verbose');
    if ($partial_config) {
      $config_import->option('partial');
    }
    $tasks[] = $this->taskDrush()
      ->drush('sqlq')
      ->arg('DELETE FROM config where name = "hs_courses_importer.importer_settings"')
      ->drush('cr');

    $tasks[] = $config_import;
    $tasks[] = $this->taskDrush()->drush('cron');
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
      ->copy('.circleci/config/behat.yml', 'tests/behat/local.yml', TRUE);
    $tasks[] = $this->taskExec('vendor/bin/behat --verbose -c tests/behat/local.yml --tags=' . implode(',', $tags));
    return $tasks;
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
    $task = $this->taskDrush()
      ->drush('site-install')
      ->args($profile)
      ->option('verbose')
      ->option('yes');
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

    $docroot = $this->getConfigValue('docroot');

    // To make things easy on setting up these tests, we'll just use the default
    // directory for all site tests. But that means we need to bring over the
    // site specific settings.php files too account for anything specific.
    if ($site != 'default' && $site != 'swshusmci') {
      $tasks[] = $this->taskFilesystemStack()
        ->copy("$docroot/sites/$site/settings.php", "$docroot/sites/default/settings.php", TRUE);
    }

    // This line is just to test connection and to prevent unwanted line at
    // the beginning of the db dump. Without this, we would get the text
    // "Warning: Permanently added the RSA host key for IP address" at the top
    // of the db dump.
    $tasks[] = $this->taskDrush()->alias("$site.prod")->drush('sql-connect');
    $tasks[] = $this->taskDrush()
      ->alias("$site.prod")
      ->drush('sql-dump')
      ->rawArg('> dump.sql');

    // At the end of the drush command, we need to remove the ssh connection
    // closed message.
    $tasks[] = $this->taskExecStack()
      ->exec("grep -v '^Connection to' $docroot/dump.sql > $docroot/clean_dump.sql");

    $tasks[] = $this->taskDrush()->drush('sql-drop')->option('yes');
    $tasks[] = $this->taskDrush()
      ->drush('sql-cli ')
      ->rawArg('< clean_dump.sql');

    $tasks[] = $this->taskExecStack()
      ->exec("rm -rf $docroot/sites/default/files");
    $tasks[] = $this->taskExecStack()
      ->exec("mkdir $docroot/sites/default/files");
    $tasks[] = $this->taskExecStack()->exec("chmod 777 -R $docroot/sites/");
    return $tasks;
  }

  /**
   * Run Functional unit tests since non-functional are tested with coverage.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function runUnitTests() {
    $docroot = $this->getConfigValue('docroot');
    $tasks = [];
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/phpunit.xml', "$docroot/core/phpunit.xml", TRUE)
      ->mkdir('../artifacts/phpunit', 777);

    $tasks[] = $this->taskExecStack()->dir($docroot)
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
    $docroot = $this->getConfigValue('docroot');
    $tasks = [];
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.circleci/config/phpunit.xml', "$docroot/core/phpunit.xml", TRUE)
      ->mkdir('artifacts/coverage-xml', 777)
      ->mkdir('artifacts/coverage-html', 777);
    $tasks[] = $this->taskExecStack()->dir($docroot)
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
    $docroot = $this->getConfigValue('docroot');
    $tasks = [];
    $tasks[] = $this->taskExecStack()
      ->exec('vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer');
    $tasks[] = $this->taskFilesystemStack()->mkdir('artifacts/phpcs');
    $tasks[] = $this->taskExecStack()
      ->exec('vendor/bin/phpcs --standard=Drupal --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . $docroot . '/' . static::TEST_DIR)
      ->exec('vendor/bin/phpcs --standard=DrupalPractice --report=junit --report-junit=artifacts/phpcs/phpcs.xml ' . $docroot . '/' . static::TEST_DIR);
    return $tasks;
  }

  /**
   * Return BLT.
   *
   * @return \Robo\Task\Base\Exec
   *   A drush exec command.
   */
  protected function blt() {
    return $this->taskExec('vendor/bin/blt')
      ->option('verbose')
      ->option('no-interaction');
  }

  /**
   * Get all available sites in multisite setup.
   *
   * @return array
   *   Array of machine names for sites.
   */
  protected function getSites() {
    $sites = [];

    foreach ($this->getConfigValue('multisites') as $site) {
      // Sandbox sites are unpredictable, so lets ignore them.
      if (strpos($site, 'sandbox') === FALSE) {
        $sites[$site] = $site;
      }
    }
    return array_rand($sites, self::SITES_TO_TEST);
  }

}
