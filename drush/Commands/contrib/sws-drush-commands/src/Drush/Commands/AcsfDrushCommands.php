<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drupal\SwsDrush\Output\Checklist;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use GuzzleHttp\Client;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class AcsfDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Perform database updates and config imports across all sites.
   *
   * Replaces `blt sws:update-environment`.
   */
  #[CLI\Command(name: 'sws:acsf:update-environment')]
  #[ClI\Option(name: 'env', description: 'ACSF environment: dev, test, or live.')]
  #[ClI\Option(name: 'separate-db-config', description: 'Run all database updates first across all sites before starting config imports.')]
  #[CLI\Option(name: 'delay-config-import', description: 'Delay the configuration import by the given number of seconds. Only when separate --separate-db-config is passed.')]
  #[CLI\Option(name: 'delay-between-sites', description: 'Delay `deploy` or `config:import` tasks between each site.')]
  #[CLI\Option(name: 'slack-token', description: 'Bot User OAuth Token. Must have "chat:write" scope permissions.')]
  #[CLI\Option(name: 'slack-channel', description: 'An encoded ID or channel name that represents a channel, private group, or IM channel to send the message to.')]
  public function updateEnvironmentSites(array $options = [
    'env' => 'dev',
    'separate-db-config' => FALSE,
    'delay-config-import' => 0,
    'delay-between-sites' => 0,
    'slack-token' => NULL,
    'slack-channel' => NULL,
  ]
  ) {
    $env = $this->commandData->options()['env'];
    $siteAliases = $this->getSiteAliases($env);

    if (!$siteAliases) {
      throw new CommandFailedException('No sites to update. Make sure aliases are created by using the `drush aliases` command.');
    }

    // Collect all the webhosts so we can run parallel processes, one on each
    // webhost.
    $updateHosts = [];
    foreach ($siteAliases as $aliasInfo) {
      $updateHosts[$aliasInfo['host']] = $aliasInfo['host'];
    }
    $updateHosts = array_values($updateHosts);

    $stack = preg_replace('/\..*/', '', reset($siteAliases)['user']);
    $threadId = $this->sendSlackMessage("Beginning updates on `$stack.01$env`.");

    $dbCommands = [];
    $configCommands = [];
    $options = ["--env=$env"];

    // Add options for the parallel tasks.
    if ($delay = $this->commandData->options()['delay-between-sites']) {
      $options[] = "--delay-between-sites=$delay";
    }
    if ($token = $this->commandData->options()['slack-token']) {
      $options[] = "--slack-token=$token";
    }
    if ($channel = $this->commandData->options()['slack-channel']) {
      $options[] = "--slack-channel=$channel";
    }
    if ($threadId) {
      $options[] = "--slack-thread=$threadId";
    }

    $separateDbConfig = $this->commandData->options()['separate-db-config'];
    foreach ($updateHosts as $host) {
      // Deploy or database update commands.
      $dbCommands[] = [
        'drush',
        'sws:acsf:update-environment:' . ($separateDbConfig ? 'database' : 'deploy'),
        '--host=' . $host,
        ...$options,
      ];

      // Config import commands.
      if ($separateDbConfig) {
        $configCommands[] = [
          'drush',
          'sws:acsf:update-environment:config',
          '--host=' . $host,
          ...$options,
        ];
      }
    }

    $reportFile = sys_get_temp_dir() . "/$stack-$env.json";
    $hostReportFiles = array_map(fn($host) => sys_get_temp_dir() . '/' . $stack . '-' . substr(md5($host), 0, 5) . "-$env.json", $updateHosts);

    // Write the report file to the system. This contains the global progress
    // messages and the reference to each file that is used in the parallel
    // jobs. Each parallel job needs a separate file to avoid simultaneous file
    // writes that can loose data.
    $fileSystem = $this->localMachineHelper()->getFilesystem();
    $fileSystem->dumpFile($reportFile, json_encode([
      'files' => $hostReportFiles,
      'messages' => [],
    ], JSON_PRETTY_PRINT));

    foreach ($hostReportFiles as $file) {
      $fileSystem->dumpFile($file, json_encode([
        'complete' => [],
        'failed' => [],
      ], JSON_PRETTY_PRINT));
    }

    $this->localMachineHelper()->executeParallel($dbCommands);

    if ($configCommands) {
      $importDelay = (int) $this->commandData->options()['delay-config-import'];
      // Use the checklist because it will display a countdown to the CLI
      // showing the remaining time, but it won't add new lines for each updated
      // display.
      if ($importDelay) {
        $checklist = new Checklist($this->output());
        $outputCallback = $this->getOutputCallback($this->output(), $checklist);

        $checklist->addItem(sprintf('Delayed config import until %s.', date('H:i:s', time() + $importDelay)));
        $this->waitTilItsTime($outputCallback, time() + $importDelay);
        $checklist->completePreviousItem();
      }

      $this->sendSlackMessage('Beginning config imports.');
      $this->localMachineHelper()->executeParallel($configCommands);
    }

    $failed = [];
    foreach ($hostReportFiles as $hostReportFile) {
      $hostReport = $this->getUpdateReport($hostReportFile);
      $failed = [...$failed, ...$hostReport['failed']];
    }
    $failed = array_unique($failed);

    $fileSystem->remove($hostReportFiles);

    $message = "Deployment complete on `$stack`.";
    if ($failed) {
      $message .= ' ' . count($failed) . ' had errors. Please review.';
    }
    $this->sendSlackMessage($message);

    if ($failed) {
      throw new CommandFailedException(sprintf('Failed sites: %s', implode(', ', $failed)));
    }
  }

  /**
   * Run `drush deploy` on every site on the host.
   */
  #[CLI\Command(name: 'sws:acsf:update-environment:deploy')]
  #[ClI\Option(name: 'env', description: 'ACSF environment: dev, test, or live.')]
  #[ClI\Option(name: 'host', description: 'ACSF Host.')]
  #[CLI\Option(name: 'delay-between-sites', description: 'Delay `deploy` or `config:import` tasks between each site.')]
  #[CLI\Option(name: 'slack-token', description: 'Bot User OAuth Token. Must have "chat:write" scope permissions.')]
  #[CLI\Option(name: 'slack-channel', description: 'An encoded ID or channel name that represents a channel, private group, or IM channel to send the message to.')]
  #[CLI\Option(name: 'slack-thread', description: 'Provide another message\'s ts value to make this message a reply. Avoid using a reply\'s ts value; use its parent instead.')]
  public function updateHostDeploy(array $options = [
    'env' => 'dev',
    'host' => NULL,
    'delay-between-sites' => 0,
    'slack-token' => NULL,
    'slack-channel' => NULL,
    'slack-thread' => NULL,
  ]
  ) {
    $aliases = $this->getSiteAliases($options['env'], $options['host']);
    $delay = (int) $options['delay-between-sites'];
    $this->performUpdates($aliases, ['deploy'], $delay);
  }

  /**
   * Run `drush updatedb` on every site on the host.
   */
  #[CLI\Command(name: 'sws:acsf:update-environment:database')]
  #[ClI\Option(name: 'env', description: 'ACSF environment: dev, test, or live.')]
  #[ClI\Option(name: 'host', description: 'ACSF Host.')]
  #[CLI\Option(name: 'delay-between-sites', description: 'Unused for database updates.')]
  #[CLI\Option(name: 'slack-token', description: 'Bot User OAuth Token. Must have "chat:write" scope permissions.')]
  #[CLI\Option(name: 'slack-channel', description: 'An encoded ID or channel name that represents a channel, private group, or IM channel to send the message to.')]
  #[CLI\Option(name: 'slack-thread', description: 'Provide another message\'s ts value to make this message a reply. Avoid using a reply\'s ts value; use its parent instead.')]
  public function updateHostDatabase(array $options = [
    'env' => 'dev',
    'host' => NULL,
    'delay-between-sites' => 0,
    'slack-token' => NULL,
    'slack-channel' => NULL,
    'slack-thread' => NULL,
  ]
  ) {
    $aliases = $this->getSiteAliases($options['env'], $options['host']);
    $this->performUpdates($aliases, ['updatedb']);
  }

  /**
   * Run `drush config:import` on every site on the host.
   */
  #[CLI\Command(name: 'sws:acsf:update-environment:config')]
  #[ClI\Option(name: 'env', description: 'ACSF environment: dev, test, or live.')]
  #[ClI\Option(name: 'host', description: 'ACSF Host.')]
  #[CLI\Option(name: 'delay-between-sites', description: 'Delay `deploy` or `config:import` tasks between each site.')]
  #[CLI\Option(name: 'slack-token', description: 'Bot User OAuth Token. Must have "chat:write" scope permissions.')]
  #[CLI\Option(name: 'slack-channel', description: 'An encoded ID or channel name that represents a channel, private group, or IM channel to send the message to.')]
  #[CLI\Option(name: 'slack-thread', description: 'Provide another message\'s ts value to make this message a reply. Avoid using a reply\'s ts value; use its parent instead.')]
  public function updateHostConfig(array $options = [
    'env' => 'dev',
    'host' => NULL,
    'delay-between-sites' => 0,
    'slack-token' => NULL,
    'slack-channel' => NULL,
    'slack-thread' => NULL,
  ]
  ) {
    $aliases = $this->getSiteAliases($options['env'], $options['host']);
    $delay = (int) $options['delay-between-sites'];
    $this->performUpdates($aliases, ['config:import', '-y'], $delay);
  }

  /**
   * Perform the drush updates for the aliases given the desired command.
   *
   * @param string[] $aliases
   *   Drush site aliases.
   * @param string[] $command
   *   Drush command arguments.
   * @param int $delay
   *   Number of seconds delay between sites.
   */
  protected function performUpdates(array $aliases, array $command, int $delay = 0) {
    $stack = preg_replace('/\..*/', '', reset($aliases)['user']);

    foreach (array_keys($aliases) as $position => $alias) {
      $hostFileName = substr(md5($aliases[$alias]['host']), 0, 5);
      $env = preg_replace('/^.*\.\d+(\w+)/', '$1', $alias);;
      $reportFile = sys_get_temp_dir() . "/$stack-$hostFileName-$env.json";

      $printOutput = round($position / count($aliases) * 100) <= 5;
      $this->performSiteUpdate($alias, $command, $reportFile, $stack, $printOutput);

      // If there's no delay to perform, just wait .1 seconds to avoid some
      // scenarios where there is simultaneous file edits.
      usleep($delay * 100000 + 10000);
    }
  }

  /**
   * Perform the command on the give site & log the status.
   *
   * @param string $alias
   *   Drush site alias.
   * @param string[] $command
   *   Drush command with options and arguments.
   * @param string $reportFile
   *   Absolute path to file.
   * @param string $stack
   *   ACSF Stack name.
   * @param bool $printOutput
   *   If the drush command should print the output.
   */
  protected function performSiteUpdate(string $alias, array $command, string $reportFile, string $stack, bool $printOutput = TRUE): void {
    $tries = 0;
    // Try 3 times to perform the command. If all 3 attempts fail, add it to the
    // report file.
    while ($tries < 3) {
      $result = $this->localMachineHelper()
        ->execute(array_merge([
          'drush',
          $alias,
        ], $command), NULL, $this->getDir(), $printOutput);
      $tries = $result->isSuccessful() ? 5 : $tries + 1;
    }

    $report = $this->getUpdateReport($reportFile);
    $report['complete'][reset($command)][] = $alias;

    if (!$result->isSuccessful()) {
      $report['failed'][] = $alias;
      $this->yell($result->getErrorOutput(), 50, 'red');
    }

    $this->localMachineHelper()
      ->getFilesystem()
      ->dumpFile($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    $this->updateMessage($command, $stack);
  }

  /**
   * Get the update report from the file, try until it succeeds.
   *
   * @param string $reportFile
   *   Absolute path to the file.
   *
   * @return array
   *   Contents of the file.
   */
  protected function getUpdateReport(string $reportFile): array {
    $reportContents = FALSE;
    $tries = 0;
    while (!$reportContents && $tries < 100) {
      try {
        // If the file can't be read or the file has an empty string, that
        // indicates the file was in the process of being written by another
        // command. Simply trying again should suffice.
        $reportContents = $this->localMachineHelper()
          ->getFilesystem()
          ->readFile($reportFile);
        $reportContents = json_decode($reportContents, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\Exception $e) {
        $tries++;
      }
    }
    return $reportContents;
  }

  /**
   * Display a status message and send a message to slack with a percent update.
   *
   * @param string[] $command
   *   Drush command being run.
   * @param string $stack
   *   Application stack.
   */
  protected function updateMessage(array $command, string $stack): void {
    $drushCommand = reset($command);

    $environment = $this->commandData->options()['env'];
    $totalAliases = count($this->getSiteAliases($environment));
    $reportFile = sys_get_temp_dir() . "/$stack-$environment.json";
    $report = $this->getUpdateReport($reportFile);

    $completed = 0;
    foreach ($report['files'] as $hostReportFile) {
      $hostReport = $this->getUpdateReport($hostReportFile);
      // Too soon in the process. The host hasn't completed a site yet.
      if (!isset($hostReport['complete'][$drushCommand])) {
        continue;
      }
      $completed += count($hostReport['complete'][$drushCommand]);
    }

    $realPercent = round($completed / $totalAliases * 100);
    $percent = floor($realPercent / 10) * 10;
    $message = sprintf('%s%% completed `%s`.', $percent, $drushCommand);

    // Make sure the message wasn't previously sent and that the percent is a
    // multiple of 10. If the percent is 100%, then no need for a message
    // because it will be provided by the command that was called.
    if (
      in_array($message, $report['messages']) ||
      $percent == 0 ||
      $percent % 10 != 0 ||
      $percent == 100
    ) {
      return;
    }

    // Record that this message was delivered to avoid duplicate notifications.
    $report['messages'][] = $message;
    $this->localMachineHelper()
      ->getFilesystem()
      ->dumpFile($reportFile, json_encode($report, JSON_PRETTY_PRINT));

    $this->yell($message);
    $this->sendSlackMessage($message);
  }

  /**
   * Get drush site aliases that match the args.
   *
   * @param string $environment
   *   Dev, test, or live environment.
   * @param string|null $host
   *   Drush alias configured host value.
   *
   * @return array
   *   Site aliases.
   */
  protected function getSiteAliases(?string $environment, ?string $host = NULL): array {
    if ($environment && !in_array($environment, ['dev', 'test', 'live'])) {
      throw new CommandFailedException('Invalid environment option');
    }

    static $allAliases = [];
    // If we haven't already fetched all the aliases, do that now. Using the
    // static variable helps to avoid unnecessary delays just to fetch all site
    // aliases.
    if (!$allAliases) {
      $result = $this->localMachineHelper()->execute([
        'drush',
        'site:alias',
        '--format=json',
      ], NULL, $this->getDir(), FALSE);

      if (!$result->isSuccessful()) {
        throw new CommandFailedException($result->getErrorOutput(), $result->getExitCode());
      }
      $allAliases = json_decode($result->getOutput(), TRUE, 512, JSON_THROW_ON_ERROR);
      ksort($allAliases);
    }
    if (!$environment && !$host) {
      return $allAliases;
    }

    // Filter out the aliases that aren't part of this environment or host.
    return array_filter($allAliases, fn($aliasInfo, $alias) => str_ends_with($alias, "01$environment") && !($host && !str_contains($aliasInfo['host'], $host)), ARRAY_FILTER_USE_BOTH);
  }

  /**
   * Send a Slack message to the Slack API.
   */
  #[CLI\Command(name: 'sws:slack-message')]
  #[CLI\Argument(name: 'message', description: 'Message text to send to slack.')]
  #[CLI\Option(name: 'slack-token', description: 'Bot User OAuth Token. Must have "chat:write" scope permissions.')]
  #[CLI\Option(name: 'slack-channel', description: 'An encoded ID or channel name that represents a channel, private group, or IM channel to send the message to.')]
  #[CLI\Option(name: 'slack-thread', description: 'Provide another message\'s ts value to make this message a reply. Avoid using a reply\'s ts value; use its parent instead.')]
  public function sendSlackMessage(string $message, $options = [
    'slack-token' => NULL,
    'slack-channel' => NULL,
    'slack-thread' => NULL,
  ]
  ): ?string {
    static $threadId = $this->commandData->options()['slack-thread'] ?? NULL;
    static $hasFailed = FALSE;

    $token = $this->commandData->options()['slack-token'] ?? NULL;
    $channel = $this->commandData->options()['slack-channel'] ?? NULL;

    // Previously failed to send slack notification. Don't try again.
    if ($hasFailed || !$token || !$channel) {
      return NULL;
    }

    $client = new Client();

    $options = [
      'headers' => [
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json; charset=utf-8',
      ],
      'json' => [
        'channel' => $channel,
        'text' => $message,
        'thread_ts' => $threadId,
      ],
    ];

    try {
      $url = 'https://slack.com/api/chat.postMessage';
      $response = $client->post($url, $options);
      $responseData = json_decode((string) $response->getBody(), TRUE);

      if (!$responseData['ok']) {
        throw new \Exception($responseData['error']);
      }

      if (!$threadId) {
        $threadId = $responseData['ts'];
      }
    }
    catch (\Throwable $e) {
      $hasFailed = TRUE;
      $this->say('Failed to send slack notification. ' . $e->getMessage());
    }
    return $responseData['ts'] ?? NULL;
  }

  /**
   * Checklist delay countdown.
   *
   * @param \Closure $outputCallback
   *   Print output callback.
   * @param int $continueTime
   *   Time to resume.
   */
  protected function waitTilItsTime(\Closure $outputCallback, int $continueTime) {
    date_default_timezone_set('America/Los_Angeles');
    while ($continueTime > time()) {
      $remainingTime = $continueTime - time();
      $outputCallback('out', sprintf("Waiting %s:%02d:%02d", floor($remainingTime / 3600), ($remainingTime / 60) % 60, $remainingTime % 60));
      sleep(1);
    }
  }

}
