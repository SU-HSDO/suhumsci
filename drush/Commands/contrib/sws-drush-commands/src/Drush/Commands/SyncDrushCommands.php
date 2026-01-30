<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Consolidation\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Drupal\SwsDrush\Output\Checklist;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Drush\Config\Loader\YamlConfigLoader;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class SyncDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  protected Checklist $checklist;

  /**
   * Sync a site from production to local and perform database updates.
   *
   * Replaces `blt drupal:sync`.
   */
  #[CLI\Command(name: 'sws:site:sync', aliases: ['drupal:sync', 'sync'])]
  #[CLI\Option(name: 'site_name', description: 'Site name to sync.')]
  #[CLI\Option(name: 'with-files', description: 'Sync files after the database.')]
  #[CLI\Option(name: 'partial', description: 'Run config imports with --partial flag.')]
  public function syncSite(array $options = [
    'site' => 'default',
    'with-files' => FALSE,
    'partial' => FALSE,
  ]
  ) {
    $this->checklist = new Checklist($this->output());
    $outputCallback = $this->getOutputCallback($this->output(), $this->checklist);

    $this->checklist->addItem('Syncing database');
    $this->syncDatabase($outputCallback, $options['site']);
    $this->checklist->completePreviousItem();

    $this->checklist->addItem('Sanitize database');
    $this->sanitizeDatabase($outputCallback, $options['site']);
    $this->checklist->completePreviousItem();

    $this->checklist->addItem('Update database');
    $this->updateDatabase($outputCallback, $options['site'], $options['partial']);
    $this->checklist->completePreviousItem();

    if ($options['with-files']) {
      $this->checklist->addItem('Syncing Files');
      $this->syncFiles($outputCallback, $options['site']);
      $this->checklist->completePreviousItem();
    }
  }

  /**
   * @param \Closure $outputCallback
   *   Output callback.
   * @param string $site_name
   *   Multisite name.
   */
  protected function syncDatabase(\Closure $outputCallback, string $site_name) {
    $remoteAlias = $this->getSiteRemoteAlias($site_name);

    $outputCallback('out', "Clearing local database");
    $this->localMachineHelper()->execute([
      'drush',
      "@$site_name.local",
      'sql-drop',
    ], $outputCallback, $this->getDir());
    $outputCallback('out', "Syncing database to local");
    $result = $this->localMachineHelper()->execute([
      'drush',
      'sql-sync',
      $remoteAlias,
      "@$site_name.local",
      '-y',
    ], $outputCallback, $this->getDir());
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed to update database: ' . $result->getErrorOutput(), $result->getExitCode());
    }
  }

  /**
   * @param \Closure $outputCallback
   *   Output callback.
   * @param string $site_name
   *   Multisite name.
   */
  protected function sanitizeDatabase(\Closure $outputCallback, string $site_name) {
    $outputCallback('out', "Sanitizing database");
    $result = $this->localMachineHelper()->execute([
      'drush',
      "@$site_name.local",
      'sql:sanitize',
      '-y',
    ], $outputCallback, $this->getDir());

    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed to update database: ' . $result->getErrorOutput(), $result->getExitCode());
    }
  }

  /**
   * @param \Closure $outputCallback
   *   Output callback.
   * @param string $site_name
   *   Multisite name.
   * @param bool $partial
   *   If config import should run with partial flag.
   */
  protected function updateDatabase(\Closure $outputCallback, string $site_name, bool $partial = FALSE) {
    $outputCallback('out', "Database updates");
    if (!$partial) {
      $this->localMachineHelper()->execute([
        'drush',
        "@$site_name.local",
        'en',
        'field_validation_legacy',
      ], $outputCallback, $this->getDir(), FALSE);

      $result = $this->localMachineHelper()->execute([
        'drush',
        "@$site_name.local",
        'deploy',
      ], $outputCallback, $this->getDir());
    }
    else {
      $this->localMachineHelper()->execute([
        'drush',
        "@$site_name.local",
        'updatedb',
        '-y',
      ], $outputCallback, $this->getDir());
      $result = $this->localMachineHelper()->execute([
        'drush',
        "@$site_name.local",
        'config:import',
        '--partial',
        '-y',
      ], $outputCallback, $this->getDir());
      $this->localMachineHelper()->execute([
        'drush',
        "@$site_name.local",
        'deploy:hook',
        '-y',
      ], $outputCallback, $this->getDir());
    }
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed to update database: ' . $result->getErrorOutput(), $result->getExitCode());
    }
  }

  /**
   * @param \Closure $outputCallback
   *   Output callback.
   * @param string $site_name
   *   Multisite name.
   */
  protected function syncFiles(\Closure $outputCallback, string $site_name) {
    $remoteAlias = $this->getSiteRemoteAlias($site_name);
    $result = $this->localMachineHelper()->execute([
      'drush',
      'rsync',
      "$remoteAlias:%files/",
      "@$site_name.local:%files",
      "--exclude-paths='styles:css:js'",
      '-v',
      '-y',
    ], $outputCallback, $this->getDir());
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed to sync public files: ' . $result->getErrorOutput(), $result->getExitCode());
    }

    $result = $this->localMachineHelper()->execute([
      'drush',
      'rsync',
      "$remoteAlias:%files-private/",
      "@$site_name.local:%files-private",
      "--exclude-paths='styles:css:js'",
      '-v',
      '-y',
    ], $outputCallback, $this->getDir());
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed to sync private files: ' . $result->getErrorOutput(), $result->getExitCode());
    }
  }

  /**
   * Sync key secret files.
   *
   * Replaces `blt sws:keys`.
   */
  #[CLI\Command(name: 'sws:keys', aliases: ['keys'])]
  #[CLI\Option(name: 'sync-ssh', description: 'Sync SSH string')]
  #[CLI\Option(name: 'sync-files', description: 'Files to sync. Use "--sync-files=foo --sync=bar" for multiple.')]
  public function syncKeys(array $options = [
    'sync-ssh' => InputOption::VALUE_REQUIRED,
    'sync-files' => [InputOption::VALUE_IS_ARRAY, InputOption::VALUE_REQUIRED],
  ]
  ) {
    $this->ensureOption('sync-ssh', fn() => $this->io()
      ->ask('SSH string'), TRUE);
    $this->ensureOption('sync-files', fn() => $this->io()
      ->ask('File to sync'), TRUE);

    $this->localMachineHelper()->checkRequiredBinariesExist(['rsync']);

    $file_system = $this->localMachineHelper()->getFilesystem();
    $file_system->mkdir($this->getDir() . '/keys');

    $ssh_url = $this->input()->getOption('sync-ssh');
    $files = $this->input()->getOption('sync-files');

    foreach ($files as &$file) {
      $file = ":$file";
    }
    $rsync_files = $ssh_url . implode(' ', $files);

    $command = "rsync --recursive --exclude .git --exclude .svn --exclude .hg --verbose --progress $rsync_files " . $this->getDir() . '/keys';
    $this->localMachineHelper()
      ->executeFromCmd($command, NULL, $this->getDir());
  }

  /**
   * Sync public and private files from prod site.
   *
   * Replaces `blt drupal:sync:files`.
   */
  #[CLI\Command(name: 'sws:site:sync-files', aliases: ['drupal:sync-files'])]
  #[CLI\Argument(name: 'site_name', description: 'Site name to sync.')]
  public function syncSiteFiles(string $site_name) {
    $outputCallback = $this->getOutputCallback($this->output(), $this->checklist);
    $this->syncFiles($outputCallback, $site_name);
  }

  /**
   * Get the remote alias of a site using the sws.yml file in the site dir.
   *
   * @param string $siteName
   *   Site machine name.
   *
   * @return string
   *   Drush remote alias.
   */
  protected function getSiteRemoteAlias(string $siteName): string {
    $fileSystem = $this->localMachineHelper()->getFilesystem();
    $configFile = Path::join($this->getDir(), 'docroot', 'sites', $siteName, 'sws.yml');
    $remoteAlias = "@$siteName.prod";

    if ($fileSystem->exists($configFile)) {
      $config = new Config();
      $loader = new YamlConfigLoader();
      $processor = new ConfigProcessor();
      $processor->extend($loader->load($configFile));
      $config->replace($processor->export());
      $ra = $config->get('site.remote-alias');
      if ($ra) {
        $remoteAlias = $ra;
      }
    }
    return str_starts_with($remoteAlias, '@') ? $remoteAlias : "@$remoteAlias";
  }

}
