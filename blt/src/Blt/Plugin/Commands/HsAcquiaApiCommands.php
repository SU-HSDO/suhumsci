<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Common\YamlMunge;
use Acquia\Blt\Robo\Exceptions\BltException;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Endpoints\SslCertificates;
use Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait;

class HsAcquiaApiCommands extends BltTasks {

  use SwsCommandTrait;

  /**
   * App id.
   *
   * @var string
   */
  protected $appId;

  /**
   * Cloud config dir.
   *
   * @var string
   */
  protected $cloudConfDir;

  /**
   * Cloud config filename.
   *
   * @var string
   */
  protected $cloudConfFileName;

  /**
   * Cloud config file path.
   *
   * @var string
   */
  protected $cloudConfFilePath;

  /**
   * Site alias dir.
   *
   * @var string
   */
  protected $siteAliasDir;

  /**
   * @var \AcquiaCloudApi\Endpoints\Databases
   */
  protected $acquiaDatabases;

  /**
   * @var \AcquiaCloudApi\Endpoints\Domains
   */
  protected $acquiaDomains;

  /**
   * @var \AcquiaCloudApi\Endpoints\SslCertificates
   */
  protected $acquiaCertificates;

  /**
   * @var \AcquiaCloudApi\Endpoints\Notifications
   */
  protected $acquiaNotifications;

  protected function setupCloudApi() {
    if ($this->acquiaApplications) {
      return;
    }
    $this->cloudConfDir = $_SERVER['HOME'] . '/.acquia';
    $this->setAppId();
    $this->cloudConfFileName = 'cloud_api.conf';
    $this->cloudConfFilePath = $this->cloudConfDir . '/' . $this->cloudConfFileName;
    $this->siteAliasDir = $this->getConfigValue('drush.alias-dir');

    $cloudApiConfig = $this->loadCloudApiConfig();
    $this->setCloudApiClient($cloudApiConfig['key'], $cloudApiConfig['secret']);
  }

  /**
   * Sets the Acquia application ID from config and prompt.
   */
  protected function setAppId() {
    if ($app_id = $this->getConfigValue('cloud.appId')) {
      $this->appId = $app_id;
    }
    else {
      $this->say("<info>To generate an alias for the Acquia Cloud, BLT requires your Acquia Cloud application ID.</info>");
      $this->say("<info>See https://docs.acquia.com/acquia-cloud/manage/applications.</info>");
      $this->appId = $this->askRequired('Please enter your Acquia Cloud application ID');
      $this->writeAppConfig($this->appId);
    }
  }

  /**
   * Loads CloudAPI token from an user input if it doesn't exist on disk.
   *
   * @return array
   *   An array of CloudAPI token configuration.
   */
  protected function loadCloudApiConfig() {
    if (!$config = $this->loadCloudApiConfigFile()) {
      $config = $this->askForCloudApiCredentials();
    }
    return $config;
  }

  /**
   * Load existing credentials from disk.
   *
   * @return bool|array
   *   Returns credentials as array on success, or FALSE on failure.
   */
  protected function loadCloudApiConfigFile() {
    if (file_exists($this->cloudConfFilePath)) {
      return (array) json_decode(file_get_contents($this->cloudConfFilePath));
    }
    else {
      return FALSE;
    }
  }

  /**
   * Interactive prompt to get Cloud API credentials.
   *
   * @return array
   *   Returns credentials as array on success.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function askForCloudApiCredentials() {
    $this->say("You may generate new API tokens at <comment>https://cloud.acquia.com/app/profile/tokens</comment>");
    $key = $this->askRequired('Please enter your Acquia cloud API key:');
    $secret = $this->askRequired('Please enter your Acquia cloud API secret:');

    // Attempt to set client to check credentials (throws exception on failure).
    $this->setCloudApiClient($key, $secret);

    $config = [
      'key' => $key,
      'secret' => $secret,
    ];
    $this->writeCloudApiConfig($config);
    return $config;
  }

  /**
   * Writes configuration to local file.
   *
   * @param array $config
   *   An array of CloudAPI configuraton.
   */
  protected function writeCloudApiConfig(array $config) {
    if (!is_dir($this->cloudConfDir)) {
      mkdir($this->cloudConfDir);
    }
    file_put_contents($this->cloudConfFilePath, json_encode($config));
    $this->say("Credentials were written to {$this->cloudConfFilePath}.");
  }

  /**
   * Sets appId value in blt.yml to disable interative prompt.
   *
   * @param string $app_id
   *   The Acquia Cloud application UUID.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function writeAppConfig($app_id) {

    $project_yml = $this->getConfigValue('blt.config-files.project');
    $this->say("Updating ${project_yml}...");
    $project_config = YamlMunge::parseFile($project_yml);
    $project_config['cloud']['appId'] = $app_id;
    try {
      YamlMunge::writeFile($project_yml, $project_config);
    }
    catch (\Exception $e) {
      throw new BltException("Unable to update $project_yml.");
    }
  }

  /**
   * Tests CloudAPI client authentication credentials.
   *
   * @param string $key
   *   The Acquia token public key.
   * @param string $secret
   *   The Acquia token secret key.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function setCloudApiClient($key, $secret) {
    try {
      $connector = new Connector([
        'key' => $key,
        'secret' => $secret,
      ]);
      $cloud_api = Client::factory($connector);

      $this->acquiaApplications = new Applications($cloud_api);
      $this->acquiaEnvironments = new Environments($cloud_api);
      $this->acquiaServers = new Servers($cloud_api);
      $this->acquiaDatabases = new Databases($cloud_api);
      $this->acquiaDomains = new Domains($cloud_api);
      $this->acquiaCertificates = new SslCertificates($cloud_api);
      $this->acquiaNotifications = new Notifications($cloud_api);

      // We must call some method on the client to test authentication.
      $this->acquiaApplications->getAll();
    }
    catch (\Exception $e) {
      throw new BltException("Unknown exception while connecting to Acquia Cloud: " . $e->getMessage());
    }
  }


  protected function getEnvironmentUuid($name) {
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $env */
    foreach ($this->acquiaEnvironments->getAll($this->appId) as $env) {
      if ($env->name == $name) {
        return $env->uuid;
      }
    }
    throw new \Exception(sprintf('Unable to find environment name %s', $name));
  }

}
