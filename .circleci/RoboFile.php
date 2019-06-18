<?php

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
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
   * Number of random sites to test behat.
   *
   * @var integer
   */
  const SITES_TO_TEST = 6;

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
   * Perform a release in github.
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
      $log = implode(';', array_filter($log));
      $changes[] = "* $log ($hash)";
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
      ->uri($github_info['name'] . '/' . $github_info['name'])
      ->description("Release $version")
      ->changes($changes)
      ->run();

    if (!$result->wasSuccessful()) {
      throw new Exception('Release was unsuccessful');
    }
  }

  /**
   * Git the information of the github remote.
   *
   * @return array
   *   Keyed array with github owner and name.
   */
  protected function getGitHubInfo() {
    $git_remote = exec('git config --get remote.origin.url');
    $git_remote = str_replace('.git', '', $git_remote);
    if (strpos($git_remote, 'https') !== FALSE) {
      $parsed_url = parse_url($git_remote);
      list($owner, $repo_name) = explode('/', trim($parsed_url['path'], '/'));
      return ['owner' => $owner, 'name' => $repo_name];
    }
    list(, $repo_name) = explode(':', $git_remote);
    str_replace('.git', '', $git_remote);

    list($owner, $repo_name) = explode('/', $repo_name);
    return ['owner' => $owner, 'name' => $repo_name];
  }

  /**
   * Get the last version from the profile.
   *
   * @return string
   *   Last semver version.
   */
  protected function getLastVersion() {
    $profile_info = Yaml::parseFile(__DIR__ . '/docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml');
    return $profile_info['version'];
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
    $divider = str_repeat('-', 80);
    $this->taskChangelog(__DIR__ . '/docs/CHANGELOG.md')
      ->setHeader("$version\n{$divider}\n_Release Date: " . date("Y-m-d") . "_\n\n")
      ->anchor("# HumSci")
      ->setBody(implode("\n", $changes))
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();
  }

  /**
   * Advance to the next SemVer version.
   *
   * The behavior depends on the parameter $stage.
   *   - If $stage is empty, then the patch or minor version of $version is
   *     incremented
   *   - If $stage matches the current stage in the current version, then add
   *     one to the stage (e.g. alpha3 -> alpha4)
   *   - If $stage does not match the current stage in the current version, then
   *     reset to '1' (e.g. alpha4 -> beta1)
   *
   * Taken from consolidation/robo library.
   *
   * @param string $version
   *   A SemVer version.
   * @param string $stage
   *   Release stage: dev, alpha, beta, rc or an empty string for stable.
   *
   * @return string
   *   New semver version.
   */
  protected function incrementVersion($version, $stage = '') {
    $stable = empty($stage);

    preg_match('/-([a-zA-Z]*)([0-9]*)/', $version, $match);
    $match += ['', '', ''];
    $versionStage = $match[1];
    $versionStageNumber = $match[2];
    if ($versionStage != $stage) {
      $versionStageNumber = 0;
    }
    $version = preg_replace('/-.*/', '', $version);
    $versionParts = explode('.', $version);
    if ($stable) {
      $versionParts[count($versionParts) - 1]++;
    }
    $version = implode('.', $versionParts);
    if (!$stable) {
      $version .= '-' . $stage;
      if ($stage != 'dev') {
        $versionStageNumber++;
        $version .= $versionStageNumber;
      }
    }
    return $version;
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
        $info_file = Yaml::parseFile($module->getRealPath());
        $info_file['version'] = $version;
        file_put_contents($module->getRealPath(), Yaml::dump($info_file));
      }
    }
  }

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
   * Command to run behat tests.
   *
   * @return \Robo\Result
   *   The result tof the collection of tasks.
   */
  public function jobRunFreshInstallBehat() {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
    $collection->addTask($this->installDrupal('config_installer'));
    $collection->addTask($this->drush()->arg('cim')->option('yes'));
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
      ->copy('.circleci/config/behat.yml', 'tests/behat/local.yml', TRUE);
    $tasks[] = $this->taskExec('vendor/bin/behat --verbose -c tests/behat/local.yml --tags=' . implode(',', $tags));
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
    $blt_config = $this->getBltConfig();
    $sites = [];
    foreach ($blt_config['multisites'] as $site) {
      // Sandbox sites are unpredictable, so lets ignore them.
      if (strpos($site, 'sandbox') === FALSE) {
        $sites[$site] = $site;
      }
    }
    return array_rand($sites, self::SITES_TO_TEST);
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
