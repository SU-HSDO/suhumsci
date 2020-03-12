<?php

namespace Example\Blt\Plugin\Commands;

use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;
use Drupal\Core\Serialization\Yaml;
use Robo\Contract\VerbosityThresholdInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Defines commands in the "humsci" namespace.
 */
class HumsciCommands extends AcHooksCommand {

  use HumsciTrait;

  /**
   * Create a new database on Acquia environment.
   *
   * @command humsci:create-database
   */
  public function createDatabase() {
    $database = $this->getMachineName('What is the name of the database? This ideally will match the site directory name. No special characters please.');
    $this->say(var_export($this->getAcquiaApi()->addDatabase($database), TRUE));
  }

  /**
   * Copies phpunit.xml with necessary changes.
   *
   * @command tests:phpunit:config
   */
  public function prePhpUnit() {
    $example = $this->getConfigValue('repo.root') . '/tests/phpunit/example.phpunit.xml';
    $destination = $this->getConfigValue('docroot') . '/core/phpunit.xml';
    if (file_exists($destination) || !file_exists($example)) {
      return;
    }

    $task = $this->taskFilesystemStack()
      ->stopOnFail()
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);

    $this->say("Generating PhpUnit configuration files...");

    $task->copy($example, $destination);
    $task->run();
    $this->getConfig()->expandFileProperties($destination);
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
   * Add a permission to all sites on the multisite.
   *
   * @param string $role
   *   Machine name of the role.
   * @param string $permission
   *   Permission to be added.
   * @param string $environment
   *   Which environment to add the permission to.
   *
   * @command drupal:perm:add
   *
   * @throws \Robo\Exception\TaskException
   */
  public function roleAddPermission($role, $permission, $environment = 'prod') {
    foreach ($this->getConfigValue('multisites') as $multisite) {
      $this->taskDrush()
        ->alias("$multisite.$environment")
        ->drush('role:perm:add')
        ->args($role)
        ->arg($permission)
        ->run();
    }
  }

  /**
   * Enables a list of modules for all sites in an environment.
   *
   * @param string $modules
   *   Comma delimited list of modules to enable.
   * @param string $environment
   *   Environment to disable modules.
   * @param string $excluded_sites
   *   Comma delimited list of sites to skip.
   *
   * @command drupal:module:enable
   *
   * @throws \Robo\Exception\TaskException
   */
  public function enableModules($modules, $environment, $excluded_sites = '') {
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
        ->drush('en')
        ->args(implode(',', $modules))
        ->drush('cr')
        ->drush('cim')
        ->option('partial')
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
   * @command drupal:sync:default:site
   * @aliases ds drupal:sync drupal:sync:default sync sync:refresh
   * @executeInVm
   */
  public function sync($options = [
    'sync-files' => FALSE,
    'partial' => FALSE,
  ]) {

    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-files'] || $this->getConfigValue('sync.files')) {
      $commands[] = 'drupal:sync:files';
    }
    $this->invokeCommands($commands);
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
      ->drush('sql-drop')
      ->drush('cache-clear drush')
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
   * Execute updates on a specific site.
   *
   * @param string $multisite
   *   Which site to update.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function updateSite($multisite) {
    $this->say("Deploying updates to <comment>$multisite</comment>...");
    $this->switchSiteContext($multisite);

    $this->invokeCommand('drupal:toggle:modules');
    $this->taskDrush()
      ->drush("cr")
      ->run();
    $this->say("Finished deploying updates to $multisite.");
  }

  /**
   * Update autoloader in composer.json.
   *
   * To allow us the ability to create TestBase.php files that can be inherited
   * by test classes, we have to specify each namespace with the appropriate
   * directory for the test files. Since composer doesn't do this dynamically,
   * we have to manually build the autoloader data with all available tests.
   *
   * @command update-autoloader
   */
  public function updateAutoloader() {
    $root = $this->getConfigValue('repo.root');
    $humsci_modules = $this->getConfigValue('docroot') . '/modules/humsci';

    $classes = [];
    foreach ($this->rglob("$humsci_modules/*Test.php") as $path) {
      $relative_path = str_replace("$root/", '', $path);

      $module_path = substr($relative_path, 0, strpos($relative_path, '/tests/'));
      $module = substr($module_path, strrpos($module_path, '/') + 1);

      $classes["Drupal\\Tests\\$module\\"] = "$module_path/tests";
    }

    $composer = json_decode(file_get_contents("$root/composer.json"), TRUE);
    $composer['autoload-dev']['psr-4'] = $classes;
    file_put_contents("$root/composer.json", str_replace('  ', ' ', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . PHP_EOL);
    shell_exec("cd $root && composer dump-autoload");
  }

  /**
   * Create a new subtheme from the base subtheme.
   *
   * @command humsci:create-subtheme
   */
  public function createSubtheme() {
    $new_theme_name = $this->askQuestion('New Theme Name?', '', TRUE);
    $new_machine_name = $this->askQuestion('New Machine Name?', preg_replace("/[^a-z]/", '_', strtolower($new_theme_name)), TRUE);
    $new_machine_name = preg_replace("/[^a-z]/", '_', strtolower($new_machine_name));

    $base_subtheme = $this->getConfigValue('docroot') . '/themes/humsci/su_humsci_subtheme';
    $new_subtheme = $this->getConfigValue('docroot') . '/themes/humsci/' . $new_machine_name;

    if (file_exists($new_subtheme)) {
      $this->yell('Subtheme already exists');
      return;
    }

    $this->taskCopyDir([$base_subtheme => $new_subtheme])->run();

    foreach ($this->rglob("$new_subtheme/*") as $file) {
      if (strpos($file, 'su_humsci_subtheme') !== FALSE) {
        $new_file = str_replace('su_humsci_subtheme', $new_machine_name, $file);
        $this->taskFilesystemStack()->rename($file, $new_file)->run();
      }
    }

    $info = Yaml::decode(file_get_contents("$new_subtheme/$new_machine_name.info.yml"));
    $info['name'] = $new_theme_name;
    $info['libraries'] = ["$new_machine_name/base"];
    $info['component-libraries'] = [
      $new_machine_name => $info['component-libraries']['su_humsci_subtheme'],
    ];
    unset($info['hidden']);
    file_put_contents("$new_subtheme/$new_machine_name.info.yml", Yaml::encode($info));
  }

  /**
   * Build the BackstopJS test json.
   *
   * @param string $site
   *   Which site to build test.
   *
   * @command humsci:build-backstop
   */
  public function buildBackstopTest($site) {
    $root = $this->getConfigValue('repo.root');
    $json = json_decode(file_get_contents("$root/backstop.json"), TRUE);
    $this->taskDrush()->alias("$site.stage")
      ->drush('pmu')->arg('shield')
      ->drush('cr')->run();
    $production_site = file_get_contents("https://$site-prod.stanford.edu");

    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML($production_site);
    $xpath = new \DOMXPath($dom);

    $link_nodes = $xpath->query("//header[@id='header']//a[@href]/@href");
    $json['scenarios'] = [];
    for ($i = 0; $i < $link_nodes->length; $i++) {
      $href = $link_nodes->item($i)->nodeValue;
      if (substr($href, 0, 1) != '/') {
        continue;
      }
      $json['scenarios'][] = [
        'label' => $href,
        'referenceUrl' => "https://$site-prod.stanford.edu$href",
        'url' => "https://$site-stage.stanford.edu$href",
      ];
    }

    file_put_contents("$root/backstop.json", json_encode($json, JSON_PRETTY_PRINT));
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
