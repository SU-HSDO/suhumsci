<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;

/**
 * Class HumsciServerCommand.
 *
 * @package Acquia\Blt\Custom\Commands
 */
class HumsciServerCommand extends AcHooksCommand {

  use HumsciTrait;

  protected $apiEndpoint = 'https://cloudapi.acquia.com/v1';

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
   * @command humsci:add-domain
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciAddDomain() {

    $username = $this->askQuestion('Acquia Username. Usually an email', '', TRUE);
    $password = $this->askHidden('Acquia Password');

    $url = "{$this->apiEndpoint}/sites/devcloud:swshumsci/envs/prod/domains.json";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $output = curl_exec($curl);
    curl_getinfo($curl);
    curl_close($curl);

    $output = json_decode($output, TRUE);
    if (isset($output['message'])) {
      $this->say('Something went wrong');
      $this->say($output['message']);
      return;
    }

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

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_POST, 1);
      $output = curl_exec($curl);
      curl_getinfo($curl);
      curl_close($curl);
      $this->say($output);

      $new_domains[$environment][] = $domain;
    }

    foreach ($new_domains as $environment => $domains) {
      $this->humsciLetsEncryptAdd($environment, ['domains' => $domains]);
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
   *
   * @command humsci:letsencrypt:add-domain
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

    if (!empty($options['domains'])) {
      $this->say('Adding domains: '. implode(', ', $options['domains']));
      $domains = array_merge($domains, $options['domains']);
    }

    $domains = array_merge($domains, $this->getDomains());
    $domains = array_unique($domains);
    $this->removeDomains($domains);

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
      ->printOutput(FALSE)
      ->run();
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
      ->printOutput(FALSE)
      ->run();
  }

  /**
   * Changes necessary configuration and adds the domain to the LE Cert.
   *
   * @command humsci:launch-site
   *
   * @param string $site
   *   The machine name of the site.
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
      ->arg('false')
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
      ->drush('cr')
      ->run();
  }

}
