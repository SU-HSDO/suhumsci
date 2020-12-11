<?php

namespace Humsci\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
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
   *  Environment machine name.
   *
   * @return string
   *   Environment UUID.
   *
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
