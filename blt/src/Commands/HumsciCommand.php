<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;
use Acquia\Blt\Robo\Exceptions\BltException;
use Consolidation\AnnotatedCommand\CommandData;

/**
 * Defines commands in the "custom" namespace.
 */
class HumsciCommand extends AcHooksCommand {

  protected $apiEndpoint = 'https://cloudapi.acquia.com/v1';

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
    'no-seven' => FALSE,
  ]) {

    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-files'] || $this->getConfigValue('sync.files')) {
      $commands[] = 'drupal:sync:files';
    }
    $this->invokeCommands($commands);

    if ($options['no-seven']) {
      $admin_info = $this->taskDrush()->drush('uinf')->options([
        'uid' => 1,
        'fields' => 'name',
        'format' => 'json',
      ])->run()->getMessage();
      $json = json_decode($admin_info, TRUE);
      $user_name = $json[1]['name'];

      return $this->taskDrush()
        ->drush('user:role:remove')
        ->arg('seven_admin_theme_user')
        ->arg($user_name)
        ->run();
    }
  }

  /**
   * Set up local environment.
   *
   * @command local:setup
   */
  public function localSetup() {
    $this->invokeCommand('setup:settings');

    $multisites = $this->getConfigValue('multisites');
    $initial_site = $this->getConfigValue('site');
    $current_site = $initial_site;

    foreach ($multisites as $multisite) {
      $this->say($multisite);
      $this->say(str_repeat('-', strlen($multisite)));
      if ($current_site != $multisite) {
        $this->switchSiteContext($multisite);
        $current_site = $multisite;
      }
      $status = $this->getInspector()->getStatus();

      // Generate settings.php.
      $multisite_dir = $this->getConfigValue('docroot') . "/sites/$multisite";
      $project_local_settings_file = "$multisite_dir/settings/local.settings.php";
      $settings_contents = file_get_contents($project_local_settings_file);

      $database_name = $this->getDatabaseName($multisite, $status['db-name']);

      $database_host = $this->askQuestion('Database Host', $status['db-hostname'], TRUE);
      $database_port = $this->askQuestion('Database Port', $status['db-port']);

      if ($multisite == 'default') {
        $database_user_name = $this->askQuestion('Database user name?', $status['db-username'], TRUE);
        $database_password = $this->askQuestion('Database password?', $status['db-password'], TRUE);
      }
      else {
        $database_user_name = $this->askQuestion("Database user name for $multisite site?", $status['db-username'], TRUE);
        $database_password = $this->askQuestion("Database password for $multisite site?", $status['db-password'], TRUE);
      }

      $settings_contents = preg_replace("/db_name = .*?;/", "db_name = '$database_name';", $settings_contents);
      $settings_contents = preg_replace("/'username' => '.*?',/", "'username' => '$database_user_name',", $settings_contents);
      $settings_contents = preg_replace("/'password' => '.*?',/", "'password' => '$database_password',", $settings_contents);
      $settings_contents = preg_replace("/'host' => '.*?',/", "'host' => '$database_host',", $settings_contents);
      $settings_contents = preg_replace("/'port' => '.*?',/", "'port' => '$database_port',", $settings_contents);

      file_put_contents($project_local_settings_file, $settings_contents);

      $status = $this->getInspector()->getStatus();
      $connection = @mysqli_connect(
        $status['db-hostname'],
        $status['db-username'],
        $status['db-password'],
        '',
        $status['db-port']
      );

      if (!$connection) {
        throw new BltException("Unable to connect to database.");
      }
      $connection->query('CREATE DATABASE IF NOT EXISTS ' . $status['db-name']);
    }
  }

  /**
   * Set up local Lando environment.
   *
   * @command local:setup:lando
   */
  public function localLandoSetup() {
    return $this->getConfigValue('multisites');
  }

  /**
   * @param $question
   * @param string $default
   * @param bool $required
   *
   * @return string
   */
  protected function askQuestion($question, $default = '', $required = FALSE) {
    if ($default) {
      $response = $this->askDefault($question, $default);
    }
    else {
      $response = $this->ask($question);
    }
    if ($required && !$response) {
      return $this->askQuestion($question, $default, $required);
    }
    return $response;
  }

  /**
   * @param string $multisite
   *
   * @return string
   */
  protected function getDatabaseName($multisite = 'default', $default = 'drupal') {
    $database_name = '';
    $count = 0;
    while (!preg_match("/^[a-z0-9_]+$/", $database_name)) {

      if (!$count) {
        $this->say('<info>Only lower case alphanumeric characters and underscores are allowed in the database name.</info>');
      }
      $question = "Database name for $multisite site?";
      if ($multisite == 'default') {
        $question = 'Database name?';
      }
      $database_name = $this->askDefault($question, $default);
      $count++;
    }
    return $database_name;
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
      ->drush('cache-clear drush')
      ->drush('sql-drop')
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

    $task->drush('cr');
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
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function updateSite($multisite) {
    $this->say("Deploying updates to <comment>$multisite</comment>...");
    $this->switchSiteContext($multisite);

    $this->invokeCommand('drupal:toggle:modules');
    $this->taskDrush()
      ->drush("updb -y")
      ->run();
    $this->taskDrush()
      ->drush("cr")
      ->run();
    $this->say("Finished deploying updates to $multisite.");
  }

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys
   */
  public function humsciKeys() {
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.prod:/mnt/gfs/swshumsci.prod/nobackup/apikeys/ @self:../keys")
      ->run();
  }

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys:send
   *
   * @param string $env
   *   Acquia environment to send the keys.
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
   * Get encryption keys from acquia.
   *
   * @command humsci:csr
   */
  public function humsciCsr() {
    //    $this->invokeCommand('humsci:keys');
    $keys_dir = $this->getConfigValue('repo.root') . '/keys/ssl';
    $conf = parse_ini_file("$keys_dir/openssl.conf", TRUE);

    $this->yell('Existing Domains');
    $this->say(implode("\n", $conf['alt_names']));
    while ($domain = $this->askQuestion('What url should be added to the CSR? Leave empty to end.')) {
      $key = 'DNS.' . (count($conf['alt_names']) + 1);
      $conf['alt_names'][$key] = $domain;
    }

    file_put_contents("$keys_dir/openssl.conf", $this->arrayToIni($conf));

    $file_prefix = date('Y-m-d') . '/' . date('H:i:s');
    if (!file_exists("$keys_dir/$file_prefix")) {
      mkdir("$keys_dir/$file_prefix", 0777, TRUE);
    }

    $csr_file = "$file_prefix/swshumsci.csr";
    $key_file = "$file_prefix/swshumsci.key";
    file_put_contents("$keys_dir/$file_prefix/alt_names.txt", implode(', ', $conf['alt_names']));

    $command = "openssl req -nodes -newkey rsa:2048 -sha256 -keyout $key_file \
                    -subj '/C=US/ST=California/L=Palo Alto/O=Stanford University/OU=Application Support Operations/CN=swshumsci.stanford.edu' \
                    -config openssl.conf -out $csr_file";
    $this->say(shell_exec("cd $keys_dir && $command"));
    // Send the new conf and csr to acquia for safe keeping.
    $this->invokeCommand('humsci:keys:send');
  }

  /**
   * Convert an array to an ini file string.
   *
   * @param array $a
   *   Data to convert.
   * @param array $parent
   *   Parent during recursion.
   *
   * @return string
   *   Ini file string.
   */
  protected function arrayToIni(array $a, array $parent = array()) {
    $out = '';
    foreach ($a as $k => $v) {
      if (is_array($v)) {
        //subsection case
        //merge all the sections into one array...
        $sec = array_merge((array) $parent, (array) $k);
        //add section information to the output
        $out .= PHP_EOL . '[' . join('.', $sec) . ']' . PHP_EOL;
        //recursively traverse deeper
        $out .= $this->arrayToIni($v, $sec);
      }
      else {
        //plain key->value case
        if (strpos($v, '(') !== FALSE || strpos($v, ')') !== FALSE) {
          $v = "\"$v\"";
        }
        $out .= "$k = $v" . PHP_EOL;
      }
    }
    return $out;
  }

  /**
   * @command humsci:add-domain
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciAddDomain() {

    $username = $this->askQuestion('Acquia Username. Usually an email', '', TRUE);
    $password = $this->askHidden('Acquia Password');

    $new_domains = [];
    while ($domain = $this->askQuestion('Domain to add. Leave empty when done')) {
      while (empty($environment = $this->askChoice('Which environment', [
        'dev',
        'test',
        'prod',
      ], ''))) {
        continue;
      }
      $url = "{$this->apiEndpoint}/sites/devcloud:swshumsci/envs/$environment/domains/$domain.json";

      $this->say($url);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_POST, 1);
      $output = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);
      $this->say($output);

      $new_domains[$environment][] = $domain;
    }

    foreach ($new_domains as $environment => $domains) {
      $this->humsciLetsEncryptAdd($environment, ['domains' => $domains]);
    }
  }

  /**
   * @command humsci:letsencrypt:list
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @return array
   *   Existing domains on the cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptList($environment = 'dev') {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $shell_command = "cd ~ && .acme.sh/acme.sh --list --listraw";
    $php_command = "return shell_exec('$shell_command');";
    $results = $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
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
   * @command humsci:letsencrypt:add-domain
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptAdd($environment = 'dev', $options = ['domains' => []]) {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $domains = $this->humsciLetsEncryptList($environment);

    $this->say('Existing domains on the cert:' . PHP_EOL . implode(PHP_EOL, $domains));
    if ($options['domains']) {
      $domains = array_merge($domains, $options['domains']);
    }
    else {
      while ($new_domain = $this->askQuestion('New Domain? Leave empty to create cert')) {
        if (strpos($new_domain, '.stanford.edu') === FALSE) {
          $this->say('Invalid domain. Must be a stanford domain.');
          continue;
        }
        if (strpos($new_domain, 'http') !== FALSE) {
          $this->say('Invalid domain. Do not include the domain protocol.');
          continue;
        }
        $domains [] = trim($new_domain, ' /\\');
      }
    }

    $primary_domain = array_shift($domains);
    asort($domains);
    $domains = "-d $primary_domain -d " . implode(' -d ', $domains);

    $directory = "/mnt/gfs/swshumsci.$environment/files/";
    $shell_command = "cd ~ && .acme.sh/acme.sh --issue $domains -w $directory";
    $php_command = "return shell_exec('$shell_command');";
    $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->run();
  }

  /**
   * @command humsci:letsencrypt:get-cert
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptGet($environment = 'dev') {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $domains = $this->humsciLetsEncryptList($environment);
    $primary_domain = array_shift($domains);

    $file = $this->askChoice('Which file would you like to get?', [
      'Certificate',
      'Private Key',
      'Intermediate Certificates',
    ], 'Certificate');
    switch ($file) {
      case 'Private Key':
        $file = "$primary_domain.key";
        break;
      case 'Intermediate Certificates':
        $file = 'ca.cer';
        break;
      default:
        $file = "$primary_domain.cer";
        break;
    }

    $shell_command = "cd ~ && cat .acme.sh/$primary_domain/$file";
    $php_command = "return shell_exec('$shell_command');";
    $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->run();
  }

}
