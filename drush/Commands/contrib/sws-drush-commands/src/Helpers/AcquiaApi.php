<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Helpers;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Endpoints\SslCertificates;

final class AcquiaApi {

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
   * @var
   */
  protected $acquiaApi;

  /**
   * Acquia applications API.
   *
   * @var \AcquiaCloudApi\Endpoints\Applications
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaApplications;

  /**
   * Acquia environments API.
   *
   * @var \AcquiaCloudApi\Endpoints\Environments
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaEnvironments;

  /**
   * Acquia servers API.
   *
   * @var \AcquiaCloudApi\Endpoints\Servers
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaServers;

  /**
   * Acquia Database API.
   *
   * @var \AcquiaCloudApi\Endpoints\Databases
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaDatabases;

  /**
   * Acquia Database Backups API.
   *
   * @var \AcquiaCloudApi\Endpoints\DatabaseBackups
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaDatabaseBackups;

  /**
   * Acquia Domains API.
   *
   * @var \AcquiaCloudApi\Endpoints\Domains
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaDomains;

  /**
   * Acquia Cert API.
   *
   * @var \AcquiaCloudApi\Endpoints\SslCertificates
   */
  public $acquiaCertificates;

  /**
   * Acquia Notifications API.
   *
   * @var \AcquiaCloudApi\Endpoints\Notifications
   *
   * @link https://github.com/typhonius/acquia-php-sdk-v2
   */
  public $acquiaNotifications;

  public function __construct(protected string $appId, protected string $appKey, protected string $appSecret) {
    $this->setCloudApiClient();
  }

  /**
   * Tests CloudAPI client authentication credentials.
   */
  protected function setCloudApiClient() {
    $connector = new Connector([
      'key' => $this->appKey,
      'secret' => $this->appSecret,
    ]);
    $this->acquiaApi = Client::factory($connector);

    $this->acquiaApplications = new Applications($this->acquiaApi);
    $this->acquiaEnvironments = new Environments($this->acquiaApi);
    $this->acquiaServers = new Servers($this->acquiaApi);
    $this->acquiaDatabases = new Databases($this->acquiaApi);
    $this->acquiaDatabaseBackups = new DatabaseBackups($this->acquiaApi);
    $this->acquiaDomains = new Domains($this->acquiaApi);
    $this->acquiaCertificates = new SslCertificates($this->acquiaApi);
    $this->acquiaNotifications = new Notifications($this->acquiaApi);
  }

  public function renewToken() {
    $this->setCloudApiClient();
  }

}
