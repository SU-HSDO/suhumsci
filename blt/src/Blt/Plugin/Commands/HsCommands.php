<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Common\EnvironmentDetector;
use Acquia\Blt\Robo\Exceptions\BltException;
use Drupal\Core\Serialization\Yaml;
use GuzzleHttp\Client;
use Robo\Exception\TaskException;
use Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait;

/**
 * Various BLT commands for H&S stack.
 */
class HsCommands extends BltTasks {

  use HsCommandTrait;

  /**
   * After code deployed, update all sites on the stack.
   *
   * @command humsci:post-code-deploy
   *
   * @aliases humsci:post-code-update
   */
  public function postCodeDeployUpdate($target_env, $deployed_tag) {
    $sites = $this->getConfigValue('multisites');
    $parallel_executions = (int) getenv('UPDATE_PARALLEL_PROCESSES') ?: 10;

    $site_chunks = array_chunk($sites, ceil(count($sites) / $parallel_executions));
    $commands = [];
    foreach ($site_chunks as $sites) {
      $commands[] = $this->blt()
        ->arg('humsci:update-sites')
        ->arg(implode(',', $sites))
        ->getCommand();
    }
    file_put_contents(sys_get_temp_dir() . '/update-report.txt', '');
    $this->taskExec(implode(" &\n", $commands) . PHP_EOL . 'wait')->run();
    $report = array_filter(explode("\n", file_get_contents(sys_get_temp_dir() . '/update-report.txt')));

    $success = [];
    $failed = [];
    foreach ($report as $line) {
      [$site, $status] = explode(':', $line);
      if ((int) $status) {
        $success[] = $site;
      }
      else {
        $failed[] = $site;
      }
    }
    unlink(sys_get_temp_dir() . '/update-report.txt');

    $this->yell(sprintf('Updated %s sites successfully.', count($success)), 100);

    if ($failed) {
      $this->yell(sprintf("Update failed for the following sites:\n%s", implode("\n", $failed)), 100, 'red');

      if (EnvironmentDetector::isAhStageEnv() || EnvironmentDetector::isAhProdEnv()) {
        $count = count($failed);
        $this->sendSlackNotification("A new deployment has been made to *$target_env* using *$deployed_tag*.\n\n*$count* sites failed to update.");
      }
      throw new \Exception('Failed update');
    }

    if (EnvironmentDetector::isAhStageEnv() || EnvironmentDetector::isAhProdEnv()) {
      $this->sendSlackNotification("A new deployment has been made to *$target_env* using *$deployed_tag*.");
    }
  }

  /**
   * Send out a slack notification.
   *
   * @param string $message
   *   Slack message.
   */
  protected function sendSlackNotification(string $message) {
    $client = new Client();
    $client->post(getenv('SLACK_NOTIFICATION_URL'), [
      'form_params' => [
        'payload' => json_encode([
          'username' => 'Acquia Cloud',
          'text' => $message,
          'icon_emoji' => 'information_source',
        ]),
      ],
    ]);
  }

  /**
   * @command humsci:update-sites
   *
   * @var string $sites
   *   List of sites to update.
   */
  public function updateSites($sites = NULL) {
    $sites = $sites ? explode(',', $sites) : $this->getConfigValue('multisites');
    foreach ($sites as $site_name) {
      $this->switchSiteContext($site_name);
      $result = $this->taskDrush()
        ->drush('updb')
        ->drush('config:import')
        ->option('partial')
        ->run();
      file_put_contents(sys_get_temp_dir() . '/update-report.txt', $site_name . ($result->wasSuccessful() ? ':1' : ':0') . PHP_EOL, FILE_APPEND);
    }
  }

  /**
   * Generate a list of emails for the given role on all sites.
   *
   * @command humsci:role-report
   */
  public function roleReport($role) {
    $information = [];
    foreach ($this->getConfigValue('multisites') as $site) {
      $emails = $this->taskDrush()
        ->alias("$site.prod")
        ->drush('sqlq')
        ->arg('SELECT d.mail FROM users_field_data d INNER JOIN user__roles r ON d.uid = r.entity_id WHERE r.roles_target_id = "' . $role . '" and d.mail NOT LIKE "%localhost%"')
        ->printOutput(FALSE)
        ->run()
        ->getMessage();

      $site_url = str_replace('_', '-', str_replace('__', '.', $site));

      if (str_contains($site_url, '.')) {
        [$first, $last] = explode('.', $site_url);
        $site_url = "$first-prod.$last";
      }
      else {
        $site_url .= "-prod";
      }
      if ($emails) {
        $emails = array_filter(explode("\n", $emails));
      }
      if(!$emails) {
        continue;
      }
      foreach ($emails as $email) {
        $information[] = [
          'site' => $site,
          'url' => "https://$site_url.stanford.edu",
          'users' => $email,
        ];
      }

    }
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Site', 'Url', 'Emails']);
    foreach ($information as $info) {
      fputcsv($out, $info);
    }
    fclose($out);
  }

  /**
   * Set up local blt settings and necessary files.
   *
   * @command humsci:local:setup
   */
  public function localSetup() {
    $repo_root = $this->getConfigValue('repo.root');
    if (file_exists("$repo_root/blt/local.blt.yml")) {
      $continue = $this->confirm('Local settings have already been set. Do you wish to remove them and start over?', TRUE);
      if (!$continue) {
        return;
      }
    }
    $dir = basename($repo_root);
    $db_name = $this->askDefault('Database Name?', 'suhumsci');
    $db_user = $this->askDefault('Database User Name?', 'root');
    $db_pass = $this->askDefault('Database Password?', 'password');
    $domain = $this->askDefault('Local Site Domain?', "docroot.$dir.loc");

    $data = [
      'project' => ['local' => ['uri' => $domain, 'hostname' => $domain]],
      'drupal' => [
        'db' => [
          'database' => $db_name,
          'username' => $db_user,
          'password' => $db_pass,
          'host' => 'localhost',
          'port' => 3306,
        ],
      ],
    ];

    file_put_contents("$repo_root/blt/local.blt.yml", Yaml::encode($data));
    $this->invokeCommands(['sws:keys', 'sbsc', 'settings']);
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
   * Run cron on all sites.
   *
   * @command drupal:cron
   */
  public function cron() {
    // Disable alias since we are targeting specific uri.
    $this->config->set('drush.alias', '');
    $failed = [];
    foreach ($this->getConfigValue('multisites') as $multisite) {
      try {
        $this->say("Running Cron on <comment>$multisite</comment>...");
        $this->switchSiteContext($multisite);

        $task = $this->taskDrush()
          ->drush("cron")
          ->run();
        if (!$task->wasSuccessful()) {
          $failed[] = $multisite;
        }
      }
      catch (\Exception $e) {
        $this->say("Unable to run cron on <comment>$multisite</comment>");
        continue;
      }
    }

    if ($failed) {
      $secrets = EnvironmentDetector::getAhFilesRoot() . '/secrets.settings.php';
      if (file_exists($secrets)) {
        include $secrets;

        $client = new Client();
        $payload = [
          'username' => 'Acquia Cloud',
          'icon' => ':information_source:',
          'text' => 'Cron failed on at least one site: ' . implode(', ', $failed),
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $client->post(getenv('SLACK_NOTIFICATION_URL'), ['body' => $encoded]);
      }
    }
  }

  /**
   * Synchronize local env from remote (remote --> local).
   *
   * Copies remote db to local db, re-imports config, and executes db updates
   * for each multisite.
   *
   * @param array $options
   *   Array of CLI options.
   *
   * @command drupal:sync:default:site
   * @aliases ds drupal:sync drupal:sync:default sync sync:refresh
   */
  public function sync(array $options = [
    'partial' => TRUE,
    'sync-public-files' => FALSE,
    'sync-private-files' => FALSE,
  ]) {
    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-public-files'] || $this->getConfigValue('sync.public-files')) {
      $commands[] = 'drupal:sync:public-files';
    }
    if ($options['sync-private-files'] || $this->getConfigValue('sync.private-files')) {
      $commands[] = 'drupal:sync:private-files';
    }
    $this->invokeCommands($commands);
  }

  /**
   * Copies remote db to local db for default site.
   *
   * @command drupal:sync:default:db
   *
   * @aliases dsb drupal:sync:db sync:db
   * @validateDrushConfig
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function syncDb() {
    $local_alias = '@' . $this->getConfigValue('drush.aliases.local');
    $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');

    $task = $this->taskDrush()
      ->alias('')
      ->drush('sql-sync')
      ->arg($remote_alias)
      ->arg($local_alias)
      ->option('extra-dump', '--no-tablespaces --insert-ignore', '=')
      ->option('--target-dump', sys_get_temp_dir() . '/tmp.target.sql.gz')
      ->option('structure-tables-key', 'lightweight')
      ->option('create-db');
    $task->drush('cr');

    if ($this->getConfigValue('drush.sanitize')) {
      $task->drush('sql-sanitize');
    }

    try {
      $result = $task->run();
    }
    catch (TaskException $e) {
      $this->say('Sync failed. Often this is due to Drush version mismatches: https://support.acquia.com/hc/en-us/articles/360035203713-Permission-denied-during-BLT-sync-or-drush-sql-sync');
      throw new BltException($e->getMessage());
    }

    return $result;
  }

  /**
   * Changes necessary configuration and adds the domain to the LE Cert.
   *
   * @param string $site
   *   The machine name of the site.
   *
   * @command humsci:launch-site
   *
   * @throws \Robo\Exception\TaskException
   */
  public function launchSite($site, $options = ['not-live' => FALSE]) {
    $new_domain = $this->getNewDomain($site, $options['not-live']);
    $new_domain = $this->askQuestion('New domain?', "https://$new_domain", TRUE);
    $this->switchSiteContext($site);
    $this->taskDrush()
      ->alias("$site.prod")
      ->drush('sset')
      ->arg('nobots')
      ->arg($options['not-live'] ? 1 : 0)
      ->option('yes')
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('domain')
      ->arg($new_domain)
      ->option('yes')
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('enabled')
      ->arg($options['not-live'] ? 0 : 1)
      ->option('yes')
      ->drush('state:set')
      ->arg('xmlsitemap_base_url')
      ->arg($new_domain)
      ->option('yes')
      ->drush('xmlsitemap:rebuild')
      ->drush('cr')
      ->run();
  }

  /**
   * Get the suggested new domain.
   *
   * @param string $site_name
   *   Drush machine name.
   * @param bool $not_live
   *   If the url should be the -prod url.
   *
   * @return string
   *   Newly constructed domain.
   */
  protected function getNewDomain(string $site_name, bool $not_live = FALSE): string {
    $site_name = str_replace('_', '-', str_replace('__', '.', $site_name));
    $site_url = explode('.', $site_name, 2);
    if ($not_live) {
      if (count($site_url) >= 2) {
        [$site, $subdomain] = $site_url;
        return "$site-prod.$subdomain.stanford.edu";
      }
      return "$site_name-prod.stanford.edu";
    }
    return "$site_name.stanford.edu";
  }

}
