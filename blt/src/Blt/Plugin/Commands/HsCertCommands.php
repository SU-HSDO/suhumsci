<?php

namespace Humsci\Blt\Plugin\Commands;

use GuzzleHttp\Client;

if (!trait_exists('Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait')) {
  return;
}

/**
 * Blt commands for LetsEncrypt things.
 */
class HsCertCommands extends HsAcquiaApiCommands {

  /**
   * Delete all old database backups.
   *
   * @command humsci:clean-backups
   */
  public function deleteOldBackups() {
    $this->connectAcquiaApi();
    $environments = $this->acquiaEnvironments->getAll($this->appId);
    $environment_uuids = [];

    foreach ($environments as $environment) {
      if ($environment->name != 'ra') {
        $environment_uuids[$environment->uuid] = $environment->name;
      }
    }

    foreach ($this->acquiaDatabases->getAll($this->appId) as $database) {
      $this->say(sprintf('Gather database backup info for %s', $database->name));

      foreach ($environment_uuids as $environment_uuid => $name) {
        $backups = $this->acquiaDatabaseBackups->getAll($environment_uuid, $database->name);
        foreach ($backups as $backup) {

          $start_at = strtotime($backup->startedAt);
          if ($backup->type == 'ondemand' && time() - $start_at > 60 * 60 * 24 * 7) {
            $this->say(sprintf('Deleting %s backup #%s on %s environment.', $database->name, $backup->id, $name));
            $this->acquiaDatabaseBackups->delete($environment_uuid, $database->name, $backup->id);
          }
        }
      }
    }
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
    'domains' => '',
    'skip-check' => FALSE,
    'force' => FALSE,
  ]) {
    $options['domains'] = array_filter(explode(',', $options['domains']));

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
    if ($domains) {
      $domains = "-d $primary_domain -d " . implode(' -d ', $domains);
    }
    else {
      $domains = "-d $primary_domain";
    }

    $directory = "/mnt/gfs/humscigryphon.$environment/tmp";

    $ssh_url = $this->getSshUrl($environment);
    $command = sprintf('ssh %s "~/.acme.sh/acme.sh --issue %s -w %s %s --debug"', $ssh_url, $domains, $directory, $options['force'] ? '--force' : '');
    return $this->taskExec($command)->run();
  }

  /**
   * Get the ssh url based on the environment name.
   *
   * @param string $environment
   *   Environment name: prod, test, dev.
   *
   * @return string
   *   Ssh location.
   */
  protected function getSshUrl($environment) {
    if ($environment == 'prod') {
      return 'humscigryphon.prod@web-42199.prod.hosting.acquia.com';
    }
    return 'humscigryphon.test@staging-25390.prod.hosting.acquia.com';
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
    $domain_environment = str_replace('test', 'stage', $environment);
    $results = $this->taskDrush()
      ->alias("default.$domain_environment")
      ->drush('eval')
      ->arg($php_command)
      ->printOutput(FALSE)
      ->run();

    $results = $results->getMessage();

    $matches = preg_grep("/^.*-$domain_environment.*/", explode("\n", $results));
    $cert = reset($matches);
    preg_match_all("/[a-z].*?\.edu/", $cert, $domains);
    $domains = $domains[0];

    return $domains;
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
    $domain_environment = str_replace('test', 'stage', $environment);
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.$domain_environment:/home/humscigryphon/.acme.sh/$cert_name/ @self:../certs")
      ->run();

    $cert = file_get_contents($this->getConfigValue('repo.root') . "/certs/$cert_name.cer");
    $key = file_get_contents($this->getConfigValue('repo.root') . "/certs/$cert_name.key");
    $intermediate = file_get_contents($this->getConfigValue('repo.root') . "/certs/ca.cer");
    $label = 'LE ' . date('Y-m-d G:i');

    $this->connectAcquiaApi();
    $environmentUuid = $this->getEnvironmentUuid($environment);
    $response = $this->acquiaCertificates->create($environmentUuid, $label, $cert, $key, $intermediate);
    $this->say($response->message);

    /** @var \AcquiaCloudApi\Response\SslCertificateResponse $cert */
    foreach ($this->acquiaCertificates->getAll($environmentUuid) as $cert) {
      if ($cert->label == $label) {
        $enable_cert = $cert->id;
      }

      if ($cert->flags->active) {
        $this->say($this->acquiaCertificates->disable($environmentUuid, $cert->id)->message);
      }

      if (strtotime($cert->expires_at) < time()) {
        $this->say($this->acquiaCertificates->delete($environmentUuid, $cert->id)->message);
      }
    }
    $this->say($this->acquiaCertificates->enable($environmentUuid, $enable_cert)->message);
    $this->taskDeleteDir($this->getConfigValue('repo.root') . '/certs')->run();
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

}
