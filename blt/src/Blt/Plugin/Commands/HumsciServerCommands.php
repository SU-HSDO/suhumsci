<?php

namespace Example\Blt\Plugin\Commands;

use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;
use GuzzleHttp\Client;

/**
 * Class HumsciServerCommand.
 *
 * @package Acquia\Blt\Custom\Commands
 */
class HumsciServerCommands extends AcHooksCommand {

  use HumsciTrait;

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys
   * @description stuff
   */
  public function humsciKeys() {
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.prod:/mnt/gfs/swshumsci.prod/nobackup/apikeys/ @self:../keys")
      ->run();
  }

  /**
   * Get encryption keys from acquia.
   *
   * @param string $env
   *   Acquia environment to send the keys.
   *
   * @command humsci:keys:send
   */
  public function humsciKeysSend($env = 'prod') {
    $send = $this->askQuestion('Are you sure you want to copy over existing keys with keys in the "keys" directory? (Y/N)', 'N', TRUE);
    $key_dir = $this->getConfigValue("key-dir.$env");
    if (strtolower($send[0]) == 'y') {
      $this->taskDrush()
        ->drush("rsync @self:../keys/ @default.$env:$key_dir")
        ->run();
    }
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
    $api = new AcquiaApi($this->getConfigValue('cloud'), $this->getConfigValue('cloud.key'), $this->getConfigValue('cloud.secret'));
    foreach (explode(',', $domains) as $domain) {
      $this->say($api->addDomain($environment, $domain));
    }
  }

  /**
   * List the LetsEncrypt certificates for the environment.
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @return array
   *   Existing domains on the cert.
   *
   * @throws \Robo\Exception\TaskException
   *
   * @command humsci:letsencrypt:list
   */
  public function humsciLetsEncryptList($environment = 'dev') {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return [];
    }

    $shell_command = "cd ~ && .acme.sh/acme.sh --list --listraw";
    $php_command = "return shell_exec('$shell_command');";
    $results = $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->printOutput(FALSE)
      ->run();

    $results = $results->getMessage();

    $domain_environment = str_replace('test', 'stage', $environment);
    $matches = preg_grep("/^.*-$domain_environment.*/", explode("\n", $results));
    $cert = reset($matches);
    preg_match_all("/[a-z].*?\.edu/", $cert, $domains);
    $domains = $domains[0];

    return $domains;
  }

  /**
   * Add domain to LetsEncrypt certificate.
   *
   * @param string $environment
   *   Which environment to add to cert.
   * @param mixed $options
   *   Parameter options.
   *
   * @command humsci:letsencrypt:add-domain
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptAdd($environment, $options = [
    'domains' => [],
    'skip-check' => FALSE,
  ]) {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }
    if (!$options['skip-check']) {
      $this->checkDomains([]);
    }
    $domains = $this->humsciLetsEncryptList($environment);

    $this->say('Existing domains on the cert:' . PHP_EOL . implode(PHP_EOL, $domains));

    if (!empty($options['domains'])) {
      $this->say('Adding domains: ' . implode(', ', $options['domains']));
      $domains = array_merge($domains, $options['domains']);
    }

    $domains = array_merge($domains, $this->getDomains());
    $domains = array_unique($domains);
    $this->removeDomains($domains);
    $domains = array_map('trim', $domains);
    if (!$options['skip-check']) {
      $this->checkDomains($domains);
    }

    $primary_domain = array_shift($domains);
    asort($domains);
    $domains = "-d $primary_domain -d " . implode(' -d ', $domains);

    $directory = "/mnt/gfs/swshumsci.$environment/files/";

    $ssh_url = sprintf('swshumsci.%s@srv-7503.devcloud.hosting.acquia.com', $environment);
    $command = sprintf('ssh %s "~/.acme.sh/acme.sh --issue %s -w %s --force --debug"', $ssh_url, $domains, $directory);
    $this->taskExec($command)->run();
  }

  /**
   * Loop through and verify each domain is available.
   *
   * @param array $domains
   *   Array of string domains to check if access is ok.
   *
   * @throws \Exception
   */
  protected function checkDomains(array $domains) {
    $this->say('Checking domains for access');
    foreach ($domains as $domain) {
      $this->say($domain);
      $client = new Client([
        'base_uri' => "http://$domain",
        'allow_redirects' => TRUE,
        'timeout' => 0,
        'verify' => FALSE,
      ]);
      $response = $client->get('/');
      if (!empty($response->getHeader('X-AH-Environment')) || $response->getHeader('via') != '1.1 login.stanford.edu') {
        continue;
      }
      throw new \Exception("Domain $domain does not point to Acquia environment");
    }
  }

  /**
   * Update the cert on Acquia Cloud using the cert files on the server.
   *
   * @param string $environment
   *   Environment to update cert.
   *
   * @command humsci:update-cert
   *
   * @throws \Robo\Exception\TaskException
   */
  public function updateCert($environment) {
    $cert_name = $environment == 'test' ? 'swshumsci-stage.stanford.edu' : "swshumsci-$environment.stanford.edu";
    $this->taskDeleteDir($this->getConfigValue('repo.root') . '/certs')->run();
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.prod:/home/swshumsci/.acme.sh/$cert_name/ @self:../certs")
      ->run();
    $api = new AcquiaApi($this->getConfigValue('cloud'), $this->getConfigValue('cloud.key'), $this->getConfigValue('cloud.secret'));

    $cert = file_get_contents($this->getConfigValue('repo.root') . "/certs/$cert_name.cer");
    $key = file_get_contents($this->getConfigValue('repo.root') . "/certs/$cert_name.key");
    $intermediate = file_get_contents($this->getConfigValue('repo.root') . "/certs/ca.cer");
    $label = 'LE ' . date('Y-m-d G:i');
    $this->say($api->addCert($environment, $cert, $key, $intermediate, $label));

    $certs = $api->getCerts($environment);
    foreach ($certs['_embedded']['items'] as $cert) {
      if ($cert['label'] == $label) {
        $this->say($api->activateCert($environment, $cert['id']));
      }

      if (strtotime($cert['expires_at']) < time()) {
        $this->say($api->removeCert($environment, $cert['id']));
      }
    }

    $this->taskDeleteDir($this->getConfigValue('repo.root') . '/certs')->run();
  }

  /**
   * Delete all on demand backups over 1 week old.
   *
   * @command humsci:clean-backups
   */
  public function cleanDatabaseBackups() {
    $api = new AcquiaApi($this->getConfigValue('cloud'), $this->getConfigValue('cloud.key'), $this->getConfigValue('cloud.secret'));
    $databases = json_decode($api->getDatabases('prod'), TRUE);
    foreach ($databases['_embedded']['items'] as $database) {
      $backups = json_decode($api->getDatabaseBackups('prod', $database['name']), TRUE);

      $this->yell(sprintf('Cleaning backups on database %s', $database['name']));
      foreach ($backups['_embedded']['items'] as $backup) {
        $last_week_time = time() - 60 * 60 * 24 * 7;

        if ($backup['type'] == 'ondemand' && strtotime($backup['completed_at']) < $last_week_time) {
          $this->say($api->deleteDatabaseBackup('prod', $database['name'], $backup['id']));
          $this->say(sprintf('deleted %s', $backup['id']));
        }
      }
    }
  }

  /**
   * Get new domains to add to Cert.
   *
   * @return array
   *   New domains.
   */
  protected function getDomains() {
    $domains = [];
    while ($new_domain = $this->askQuestion('New Domain? Leave empty to create cert')) {
      if (strpos($new_domain, '.stanford.edu') === FALSE) {
        $this->say('Invalid domain. Must be a stanford domain.');
        continue;
      }
      if (strpos($new_domain, 'http') !== FALSE) {
        $this->say('Invalid domain. Do not include the domain protocol.');
        continue;
      }
      $domains[] = trim($new_domain, ' /\\');
    }
    return $domains;
  }

  /**
   * Ask the user if there are any domains on the current cert to be removed.
   *
   * @param array $existing_domains
   *   Array of current domains.
   *
   * @return array
   *   Array of remaining domains.
   */
  protected function removeDomains(array &$existing_domains) {
    $domains = [];
    $choices = array_merge(['Done'], $existing_domains);
    while ($remove_domain = $this->askChoice('Would you like to remove a domain?', $choices)) {
      if ($remove_domain == 'Done') {
        break;
      }
      unset($existing_domains[array_search($remove_domain, $existing_domains)]);
      $choices = array_merge(['Done'], $existing_domains);
    }
    return $domains;
  }

  /**
   * Get LetsEncrypt certificate file contents.
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @command humsci:letsencrypt:get-cert
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptGet($environment) {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $domains = $this->humsciLetsEncryptList($environment);
    $primary_domain = array_shift($domains);

    $files = $this->getWhichCertFiles($primary_domain);

    foreach ($files as $file) {
      $shell_command = "cd ~ && cat .acme.sh/$primary_domain/$file";
      $php_command = "return shell_exec('$shell_command');";

      $message = 'Certificate';
      if (strpos($file, '.key') !== FALSE) {
        $message = 'Private Key';
      }
      elseif ($file == 'ca.cer') {
        $message = 'Intermediate Certificates';
      }

      $this->say(str_repeat('-', strlen($message) + 4));
      $this->say("  $message  ");
      $this->say(str_repeat('-', strlen($message) + 4));

      $this->taskDrush()
        ->alias($this->getConfigValue('drush.aliases.remote'))
        ->drush('eval')
        ->arg($php_command)
        ->run();
    }
  }

  /**
   * Ask the user and get which cert files to display.
   *
   * @param string $primary_domain
   *   Primary domain on the cert.
   *
   * @return array
   *   List of file names.
   */
  protected function getWhichCertFiles($primary_domain) {
    $file = $this->askChoice('Which file would you like to get?', [
      '- All -',
      'Certificate',
      'Private Key',
      'Intermediate Certificates',
    ], 'Certificate');
    switch ($file) {
      case '- All -':
        $files = [
          "$primary_domain.cer",
          "$primary_domain.key",
          'ca.cer',
        ];
        break;

      case 'Private Key':
        $files = ["$primary_domain.key"];
        break;

      case 'Intermediate Certificates':
        $files = ['ca.cer'];
        break;

      default:
        $files = ["$primary_domain.cer"];
        break;
    }
    return $files;
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
  public function launchSite($site) {
    $new_domain = preg_replace('/[^a-z]/', '-', $site);
    $new_domain = $this->askQuestion('New domain?', "https://$new_domain.stanford.edu", TRUE);
    $this->switchSiteContext($site);
    $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.prod'))
      ->drush('cset')
      ->arg('config_split.config_split.not_live')
      ->arg('status')
      ->arg(0)
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('domain')
      ->arg($new_domain)
      ->drush('cset')
      ->arg('domain_301_redirect.settings')
      ->arg('enabled')
      ->arg(1)
      ->drush('pmu')
      ->arg('nobots')
      ->drush('state:set')
      ->arg('xmlsitemap_base_url')
      ->arg($new_domain)
      ->drush('xmlsitemap:rebuild')
      ->drush('cset')
      ->arg('hs_courses_importer.importer_settings')
      ->arg('base_url')
      ->arg($new_domain)
      ->drush('cr')
      ->run();
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

    $api = new AcquiaApi($this->getConfigValue('cloud'), $this->getConfigValue('cloud.key'), $this->getConfigValue('cloud.secret'));
    $sites = $this->getSitesToSync($api, $task_started, $options);
    if (!$this->confirm(sprintf('Are you sure you wish to stage the following sites: <comment>%s</comment>', implode(', ', $sites)))) {
      return;
    }
    $count = count($sites);
    $copy_sites = array_splice($sites, 0, 4);

    foreach ($copy_sites as $site) {
      $this->say("Copying $site database to staging.");
      $api->copyDatabase($site, 'prod', 'test');
    }

    while (!empty($sites)) {
      echo '.';
      sleep(10);
      $finished_databases = $this->getCompletedDatabaseCopies($api, $task_started);

      if ($finished = array_intersect($copy_sites, $finished_databases)) {
        echo PHP_EOL;
        foreach ($finished as $copied_db) {
          $db_position = array_search($copied_db, $copy_sites);
          $new_site = array_splice($sites, 0, 1);
          $new_site = reset($new_site);
          $copy_sites[$db_position] = $new_site;
          $this->say("Copying $new_site database to staging.");
          $api->copyDatabase($new_site, 'prod', 'test');
        }
      }
    }
    $this->yell("$count database have been copied to staging.");
  }

  /**
   * Get an overall list of database names to sync.
   *
   * @param \Example\Blt\Plugin\Commands\AcquiaApi $api
   *   Acquia API.
   * @param int $task_started
   *   Time to compare the completed task.
   * @param array $options
   *   Array of keyed command options.
   *
   * @return array
   *   Array of database names to sync.
   */
  protected function getSitesToSync(AcquiaApi $api, $task_started, array $options) {
    $finished_databases = $this->getCompletedDatabaseCopies($api, $task_started);

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

  /**
   * Get a list of all databases that have finished copying after a time.
   *
   * @param \Example\Blt\Plugin\Commands\AcquiaApi $api
   *   Acquia API.
   * @param int $time_comparison
   *   Time to compare the completed task.
   *
   * @return array
   *   Array of database names.
   */
  protected function getCompletedDatabaseCopies(AcquiaApi $api, $time_comparison) {
    $databases = [];
    $notifications = json_decode($api->getNotifications(), TRUE);
    foreach ($notifications['_embedded']['items'] as $notification) {
      if (
        $notification['event'] == 'DatabaseCopied' &&
        $notification['status'] == 'completed' &&
        strtotime($notification['created_at']) >= $time_comparison
      ) {
        $databases = array_merge($databases, $notification['context']['database']['names']);
      }
    }
    return array_values(array_unique($databases));
  }

}
