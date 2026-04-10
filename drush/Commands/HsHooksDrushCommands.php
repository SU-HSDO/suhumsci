<?php

declare(strict_types=1);

namespace Drush\Commands;

use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Attributes as CLI;

/**
 * Custom H&S Drush hooks. Hooks are used to run code before or after a Drush
 * command, or to alter command arguments.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HsHooksDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Pre-site sync hooks.
   */
  #[CLI\Hook(type: 'pre-command', target: 'sws:site:sync')]
  public function preSiteSync() {
    // Delete local config_ignore configuration temporarily to preserver custom
    // site configuration durying sync.
    $root = $this->getDir();
    $this->localMachineHelper()->execute([
      'rm',
      $root . '/config/envs/local/config_split.patch.config_ignore.settings.yml',
    ], NULL, $this->getDir());
  }

  /**
   * Post-site sync hooks.
   */
  #[CLI\Hook(type: 'post-command', target: 'sws:site:sync')]
  public function postSiteSync() {
    // Restore local config_ignore configuration after sync.
    $root = $this->getDir();
    $this->localMachineHelper()->execute([
      'git',
      'checkout',
      $root . '/config/envs/local/config_split.patch.config_ignore.settings.yml',
    ], NULL, $this->getDir());
  }
}