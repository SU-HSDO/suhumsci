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

/**
 * Class HsAcquiaApiCommands.
 *
 * @package Humsci\Blt\Plugin\Commands
 */
class HsAcquiaApiCommands extends BltTasks {

  use SwsCommandTrait;

  /**
   * Get the environment UUID for the application from the machine name.
   *
   * @param string $name
   *
   *
   * @return string
   *   Environment UUID.
   * @throws \Exception
   */
  protected function getEnvironmentUuid(string $name) {
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $env */
    foreach ($this->acquiaEnvironments->getAll($this->appId) as $env) {
      if ($env->name == $name) {
        return $env->uuid;
      }
    }
    throw new \Exception(sprintf('Unable to find environment name %s', $name));
  }

}
