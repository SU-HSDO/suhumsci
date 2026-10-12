<?php

namespace Drush\Commands;

use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;

/**
 * Run a single Ultimate Cron job across every multisite.
 *
 * The SWS package ships sws:multisite:cron and sws:multisite:cron:scheduler
 * for two fixed jobs. This runs any job by ID so an Acquia scheduled job can
 * target one hook, such as hs_algolia_cron, on its own interval without
 * running full cron.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HsCronDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Run one Ultimate Cron job on every multisite.
   */
  #[CLI\Command(name: 'humsci:multisite:cron:run')]
  #[CLI\Argument(name: 'job', description: 'Ultimate Cron job ID, for example hs_algolia_cron.')]
  #[CLI\Option(name: 'force', description: 'Run the job even if it is not yet due on its schedule. Locks are still respected.')]
  #[CLI\Usage(name: 'drush humsci:multisite:cron:run hs_algolia_cron --force', description: 'Process queued Algolia deletions on every site now.')]
  #[CLI\Usage(name: 'drush humsci:multisite:cron:run search_api_cron --force', description: 'Index pending Search API items on every site now.')]
  public function run(string $job, array $options = ['force' => FALSE]): void {
    $multisites = $this->getConfig()->get('command.sws.options.multisites') ?? ['default'];
    $failed = [];

    foreach ($multisites as $site) {
      $this->output()->writeln("Running $job on <comment>$site</comment>...");

      $command = ['drush', '@self', 'cron:run', $job, "--uri=$site"];
      if ($options['force']) {
        $command[] = '--force';
      }

      $result = $this->localMachineHelper()->execute($command, NULL, $this->getDir());
      if ($result->isSuccessful()) {
        $this->output()->writeln("$job on <comment>$site</comment> completed successfully.");
      }
      else {
        $failed[] = $site;
        $this->output()->writeln("$job on <comment>$site</comment> failed.");
      }
    }

    if ($failed) {
      $message = sprintf('%s failed on: %s', $job, implode(', ', $failed));
      $this->output()->writeln($message);
      $this->sendWebhookNotification($message);
      throw new \RuntimeException($message);
    }

    $this->output()->writeln("$job completed successfully on all sites.");
  }

}
