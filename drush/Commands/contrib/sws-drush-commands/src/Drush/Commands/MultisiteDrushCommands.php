<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Consolidation\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Drush\Boot\DrupalBootLevels;
use Drush\Config\Loader\YamlConfigLoader;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class MultisiteDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Generates a new multisite.
   *
   * Replaces `blt multisite`.
   */
  #[CLI\Command(name: 'sws:multisite:new-site', aliases: ['multisite'])]
  #[CLI\Argument(name: 'site_name', description: 'Machine name of the multisite.')]
  #[CLI\Option(name: 'no-update-drush', description: 'Flag to disable update the drush/drush.yml with the new multisite name.')]
  #[CLI\Option(name: 'multisites', description: 'List of existing multisite.')]
  #[CLI\Usage(name: 'drush multisite foobar', description: 'New site and updates to drush config')]
  #[CLI\Usage(name: 'drush multisite foobar --no-update-drush', description: 'New site and no update to drush config')]
  public function newMultisite(string $site_name, array $options = [
    'no-update-drush' => InputOption::VALUE_NEGATABLE,
    'multisites' => ['default'],
  ]
  ) {
    $this->say("This will generate a new site in the docroot/sites directory.");

    $new_site_dir = $this->getDir() . '/docroot/sites/' . $site_name;

    if (file_exists($new_site_dir)) {
      throw new CommandFailedException("Cannot generate new multisite, $new_site_dir already exists!");
    }
    $default_site_dir = $this->getDir() . '/docroot/sites/default';

    $this->localMachineHelper()->execute([
      'rsync',
      '-r',
      $default_site_dir . '/',
      $new_site_dir,
      '--exclude',
      'local.settings.php',
      '--exclude',
      'files',
    ], NULL, $this->getDir());
    $this->say("New site generated at <comment>$new_site_dir</comment>");

    // Update sws.yml for the new site to set the correct remote-alias.
    $sws_yml_path = $new_site_dir . '/sws.yml';
    if (file_exists($sws_yml_path)) {
      $sws = Yaml::parseFile($sws_yml_path);
      if (!isset($sws['site'])) {
        $sws['site'] = [];
      }
      $sws['site']['remote-alias'] = "$site_name.prod";
      file_put_contents($sws_yml_path, Yaml::dump($sws, 2, 2));
      $this->say("Updated remote-alias in $sws_yml_path to @$site_name.prod");
    }

    if (file_exists($this->getDir() . '/drush/sites/default.site.yml')) {
      $new_alias = Yaml::parseFile($this->getDir() . '/drush/sites/default.site.yml');
      // Set the URI for each alias based on the environment.
      foreach ($new_alias as $env => &$alias) {
        if ($env === 'local') {
          $alias['uri'] = $site_name;
        } else {
          $alias['uri'] = "{$site_name}-{$env}.stanford.edu";
        }
      }
      file_put_contents($this->getDir() . "/drush/sites/$site_name.site.yml", Yaml::dump($new_alias, 99, 2));
      $this->say("Drush aliases generated: @$site_name");
    }

    if ($options['no-update-drush'] !== TRUE) {
      $drush_config = Yaml::parseFile($this->getDir() . '/drush/drush.yml');

      $options['multisites'][] = $site_name;
      asort($options['multisites']);
      $drush_config['command']['sws']['options']['multisites'] = array_values($options['multisites']);

      file_put_contents($this->getDir() . '/drush/drush.yml', Yaml::dump($drush_config, 99, 2));
    }
  }

  /**
   * Install Drupal.
   *
   * Replaces `blt drupal:install`.
   */
  #[CLI\Command(name: 'sws:multisite:install', aliases: [
    'drupal:install',
    'di',
  ])]
  #[CLI\Option(name: 'site', description: 'Machine name of site.')]
  public function siteInstall(array $options = [
    'site' => 'default',
  ]
  ) {
    $defaultProfile = $this->getConfig()
      ->get('project.profile') ?: 'stanford_profile';
    $fileSystem = $this->localMachineHelper()->getFilesystem();
    $siteConfig = Path::join($this->getDir(), 'docroot', 'sites', $options['site'], 'sws.yml');
    $siteProfile = NULL;

    if ($fileSystem->exists($siteConfig)) {
      $config = new Config();
      $loader = new YamlConfigLoader();
      $processor = new ConfigProcessor();
      $processor->extend($loader->load($siteConfig));
      $config->replace($processor->export());
      $siteProfile = $config->get('site.profile');
    }

    $this->localMachineHelper()->execute([
      'drush',
      'site-install',
      $siteProfile ?: $defaultProfile,
      "--uri={$options['site']}",
      '-v',
      '-y',
    ], NULL, $this->getDir());
  }

  /**
   * Run database and config updates on all multisites.
   */
  #[CLI\Command(name: 'sws:multisite:update')]
  #[CLI\Option(name: 'multisites', description: 'List of sites to update')]
  #[CLI\Option(name: 'partial', description: 'Import config with --partial flag.')]
  #[CLI\Option(name: 'show-output', description: 'Display database updates and config update process.')]
  public function updateSites(array $options = [
    'multisites' => ['default'],
    'partial' => FALSE,
    'show-output' => FALSE,
  ]
  ) {
    $multiSites = $this->input()->getOption('multisites');

    foreach ($multiSites as $site) {
      $site_dir = $this->getDir() . '/docroot/sites/' . $site;
      $this->say("Beginning updates for $site.");

      if (!$options['partial']) {
        $result = $this->localMachineHelper()->execute([
          'drush',
          '@self',
          'deploy',
          "--uri=$site",
        ], NULL, $site_dir, $options['show-output']);
      }
      else {
        $result = $this->localMachineHelper()->execute([
          'drush',
          '@self',
          'updb',
          '-y',
          "--uri=$site",
        ], NULL, $site_dir);
        if ($result->isSuccessful()) {
          $result = $this->localMachineHelper()->execute([
            'drush',
            '@self',
            'config:import',
            '-y',
            "--uri=$site",
          ], NULL, $site_dir, $options['show-output']);
          if ($result->isSuccessful()) {
            $result = $this->localMachineHelper()->execute([
              'drush',
              '@self',
              'deploy:hook',
              '-y',
              "--uri=$site",
            ], NULL, $site_dir, $options['show-output']);
          }
        }
      }

      if ($result->isSuccessful()) {
        $this->say("Successfully updated $site");
        file_put_contents(sys_get_temp_dir() . '/success-report.txt', $site . PHP_EOL, FILE_APPEND);
        continue;
      }

      $this->say("An error occurred during update on $site:");
      $this->say($result->getErrorOutput());
      file_put_contents(sys_get_temp_dir() . '/failed-report.txt', $site . PHP_EOL, FILE_APPEND);
    }
  }

  /**
   * Run database and config updates on all multisites.
   */
  #[CLI\Command(name: 'sws:multisite:update:parallel')]
  #[CLI\Option(name: 'multisites', description: 'List of sites to update')]
  #[CLI\Option(name: 'partial', description: 'Import config with --partial flag.')]
  #[CLI\Option(name: 'parallel-processes', description: 'How many parallel processes to run simultaneously.')]
  #[CLI\Option(name: 'show-output', description: 'Display database updates and config update process.')]
  public function updateSitesParallel(array $options = [
    'multisites' => ['default'],
    'partial' => FALSE,
    'parallel-processes' => 5,
    'show-output' => FALSE,
  ]
  ) {
    file_put_contents(sys_get_temp_dir() . '/success-report.txt', '');
    file_put_contents(sys_get_temp_dir() . '/failed-report.txt', '');

    $parallel_executions = (int) getenv('UPDATE_PARALLEL_PROCESSES') ?: $options['parallel-processes'];
    $multiSites = $this->input()->getOption('multisites');
    $multiSites = array_filter($multiSites, [$this, 'isDrupalInstalled']);

    $site_chunks = [];
    $i = 0;
    while ($multiSites) {
      $site = array_splice($multiSites, 0, 1);
      $site_chunks[$i][] = reset($site);
      $i++;
      if ($i >= $parallel_executions) {
        $i = 0;
      }
    }

    $commands = [];
    foreach ($site_chunks as $sites) {
      foreach ($sites as &$site) {
        $site = '--multisites=' . $site;
      }

      $command = ['drush', 'sws:multisite:update', ...$sites];
      if ($options['partial']) {
        $command[] = '--partial';
      }
      if ($options['show-output']) {
        $command[] = '--show-output';
      }
      $commands[] = $command;
    }
    $this->localMachineHelper()->executeParallel($commands);

    $success_report = array_filter(explode("\n", file_get_contents(sys_get_temp_dir() . '/success-report.txt')));
    $failed_report = array_filter(explode("\n", file_get_contents(sys_get_temp_dir() . '/failed-report.txt')));

    $this->yell(sprintf('Updated %s sites successfully.', count($success_report)), 100);

    if ($failed_report) {
      $this->yell(sprintf("Update failed for the following sites:\n%s", implode("\n", $failed_report)), 100, 'red');
      throw new CommandFailedException('Failed update');
    }
  }

  /**
   * Sync all multisite databases from production to a target environment 
   * (default: staging).
   *
   * Copies databases from prod to the specified environment, with options to 
   * exclude sites, force copy, and suppress notifications.
   * 
   * Replaces `blt stage`.
   *
   * @option exclude Comma separated list of database names to skip.
   * @option force Force copying of databases even if they were already copied recently.
   * @option env Target environment (default: test).
   * @option no-notify Suppress Slack notification.
   */
  #[CLI\Command(name: 'sws:multisite:sync-stage')]
  #[CLI\Option(name: 'exclude', description: 'Comma separated list of site names to skip.')]
  #[CLI\Option(name: 'force', description: 'Force copying of databases even if they were already copied recently.')]
  #[CLI\Option(name: 'env', description: 'Target environment (default: test).')]
  #[CLI\Option(name: 'no-notify', description: 'Suppress Slack notification.')]
  #[CLI\Option(name: 'app-id', description: 'Acquia application ID')]
  #[CLI\Option(name: 'app-key', description: 'Acquia API key')]
  #[CLI\Option(name: 'app-secret', description: 'Acquia API secret')]
  public function syncSitesStaging(
    $options = [
      'exclude' => NULL,
      'force' => FALSE,
      'env' => 'test',
      'no-notify' => FALSE,
      'app-id' => InputOption::VALUE_REQUIRED,
      'app-key' => InputOption::VALUE_REQUIRED,
      'app-secret' => InputOption::VALUE_REQUIRED,
  ]
  ) {
    $acquiaApi = $this->getAcquiaApi();
    $appId = $this->input()->getOption('app-id');

    // Parse options.
    $from_env = 'prod';
    $to_env = $options['env'] ?? 'test';
    $exclude = $options['exclude'] ? array_map('trim', explode(',', $options['exclude'])) : [];
    $force = !empty($options['force']);
    $no_notify = !empty($options['no-notify']);

    // Get environment UUIDs.
    $environments = $acquiaApi->acquiaEnvironments->getAll($appId);
    $env_uuids = [];
    foreach ($environments as $env) {
      $env_uuids[$env->name] = $env->uuid;
    }
    $from_uuid = $env_uuids[$from_env] ?? null;
    $to_uuid = $env_uuids[$to_env] ?? null;
    if (!$from_uuid || !$to_uuid) {
      $this->yell("Could not find UUIDs for prod or target environment.", 40, 'red');
      return;
    }

    // Get all databases/sites.
    $databases = $acquiaApi->acquiaDatabases->getNames($appId);
    $sites = [];
    foreach ($databases as $db) {
      if (!in_array($db->name, $exclude)) {
        $sites[] = $db->name;
      }
    }
    if (empty($sites)) {
      $this->say('No sites to sync.');
      return;
    }

    if (!$force && !$this->confirm(
      sprintf(
        'This will sync the databases for the following sites from prod to the %s environment:<comment> %s. </comment>Continue?',
        $to_env,
        implode(', ', $sites)
      )
    )) {
      return;
    }

    foreach ($sites as $database_name) {
      $this->output()->writeln("<info>Copying database $database_name from $from_env to $to_env</info>");
      $acquiaApi->acquiaDatabases->copy($from_uuid, $database_name, $to_uuid);
      $this->output()->writeln("<comment>Waiting 1 minute before next copy...</comment>");
      sleep(60); // 1 minute
    }

    $this->yell(count($sites) . " databases have been copied to $to_env.");

    if (!$no_notify && getenv('SLACK_NOTIFICATION_URL')) {
      $this->say('Slack notification sent.');
    }
  }

  /**
   * Run drush status to check if a site is installed.
   *
   * @param string $site
   *   Site name.
   *
   * @return bool
   *   If a site is installed.
   */
  protected function isDrupalInstalled(string $site): bool {
    $site_dir = $this->getDir() . '/docroot/sites/' . $site;
    if (!file_exists($site_dir)) {
      $this->say(sprintf('No site directory found for %s.', $site));
      return FALSE;
    }
    $install_profile = $this->localMachineHelper()->execute([
      'drush',
      '@self',
      'status',
      "--uri=$site",
      '--fields=install-profile',
      '--format=string',
    ], NULL, $site_dir, FALSE)->getOutput();

    if (!preg_match('/([\w_])+/', $install_profile)) {
      $this->say(sprintf('No installed site detected for %s.', $site));
      return FALSE;
    }
    return TRUE;
  }

}
