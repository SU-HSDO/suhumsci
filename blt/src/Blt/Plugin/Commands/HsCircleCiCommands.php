<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Finder\Finder;

/**
 * BLT commands that are intended for CircleCI.
 */
class HsCircleCiCommands extends BltTasks {

  /**
   * Update all dependencies and re-export the configuration.
   *
   * @command circleci:update
   */
  public function updateDependencies() {
    $collection = $this->collectionBuilder();
    $collection->addTaskList($this->setupSite());
    $collection->addTask($this->blt()->arg('drupal:install'));

    $collection->addTask($this->taskDrush()
      ->drush('config-import')
      ->option('yes'));
    $collection->addTask($this->taskComposerUpdate()->option('no-interaction'));

    $collection->addTask($this->taskDrush()->drush('updb')->option('yes'));
    $collection->addTask($this->taskDrush()
      ->drush('config:export')
      ->option('yes'));

    $collection->addTask($this->taskGitStack()
      ->checkout($_ENV['CIRCLE_BRANCH'])
      ->add('composer.lock config')
      ->commit('Updated dependencies ' . date('M j Y'))
      ->push('origin', $_ENV['CIRCLE_BRANCH']));

    return $collection->run();
  }

  /**
   * Create a new branch for the next release.
   *
   * @command circleci:new-release-branch
   *
   * @param string $last_version
   *   Semver version.
   */
  public function jobNewReleaseBranch($last_version) {
    // Increment the last version by 1.
    $new_version = $this->incrementVersion($last_version);
    $this->yell("Creating new release: $new_version");

    // Set module and profile version. Then update the changelog.
    $this->setVersions($new_version);

    $new_branch = "$new_version-release";
    // Create the new release branch in github.
    $this->taskGitStack()
      ->checkout("-b $new_branch")
      ->run();

    $this->taskGitStack()
      ->add('-A')
      ->commit("$new_version")
      ->push('origin', $new_branch)
      ->run();

    $message = "$new_version Release" . PHP_EOL . PHP_EOL . '# DO NOT DELETE';
    $this->taskExec("hub pull-request -b develop -m '$message'")
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
      // '*/modules/humsci/*',
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
   * Perform some tasks to prepare the drupal environment.
   *
   * @return \Robo\Contract\TaskInterface[]
   *   List of tasks to set up site.
   */
  protected function setupSite() {
    $tasks[] = $this->taskExec('dockerize -wait tcp://localhost:3306 -timeout 1m');
    $tasks[] = $this->taskExec('apachectl stop; apachectl start');
    return $tasks;
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
}
