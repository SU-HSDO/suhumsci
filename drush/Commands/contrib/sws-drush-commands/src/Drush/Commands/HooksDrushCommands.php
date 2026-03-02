<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HooksDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Set profile argument for site install.
   */
  #[CLI\Hook(type: 'pre-command', target: 'site:install')]
  public function preSiteInstall() {
    // Drush 12 uses `profile` drush 13 uses `recipeOrProfile`.
    $arg_names = ['profile', 'recipeOrProfile'];
    foreach ($arg_names as $arg_name) {
      if (!$this->input()->hasArgument($arg_name)) {
        try {
          $this->input()->setArgument($arg_name, [
            $this->getConfig()
              ->get('project.profile') ?: 'stanford_profile',
          ]);
        }
        catch (\Exception $exception) {
        }
      }
    }
  }

  /**
   * Import configs after site install.
   */
  #[CLI\Hook(type: 'post-command', target: 'site:install')]
  public function postSiteInstall() {
    $uri = $this->input()->getOption('uri');
    $this->localMachineHelper()->execute(['drush', 'cim', "--uri=$uri", '-y']);
  }

}
