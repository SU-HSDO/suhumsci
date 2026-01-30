<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drupal\SwsDrush\Helpers\AcquiaApi;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class AcquiaCleanupDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Delete on-demand database backups that are old.
   */
  #[CLI\Command(name: 'sws:acquia:clean-databases')]
  #[CLI\Option(name: 'app-id', description: 'Acquia application ID')]
  #[CLI\Option(name: 'app-key', description: 'Acquia API key')]
  #[CLI\Option(name: 'app-secret', description: 'Acquia API secret')]
  #[CLI\Option(name: 'minimum-age', description: 'Minimum age in seconds from the current time.')]
  #[CLI\Option(name: 'environment', description: 'Acquia environment(s) to clean. Multiple environments use --environment=dev --environment=stage')]
  public function cleanOldDatabases(array $options = [
    'app-id' => InputOption::VALUE_REQUIRED,
    'app-key' => InputOption::VALUE_REQUIRED,
    'app-secret' => InputOption::VALUE_REQUIRED,
    'minimum-age' => 604800,
    'environment' => ['dev', 'stage', 'prod'],
  ]
  ): void {
    $acquiaApi = $this->getAcquiaApi();
    $appId = $this->input()->getOption('app-id');

    $application = $acquiaApi->acquiaApplications->get($appId);
    if ($application->hosting->type == 'acsf') {
      throw new CommandFailedException('ACSF Applications are not supported. Only single applications (ACE, ACN) are supported.');
    }

    $environments = $this->safelyRunAcquiaRequest($acquiaApi, [
      $acquiaApi->acquiaEnvironments,
      'getAll',
    ], $appId);
    $environments = array_filter((array) $environments, fn($env) => in_array($env->name, $options['environment']));

    $environment_uuids = [];
    foreach ($environments as $environment) {
      $environment_uuids[$environment->uuid] = $environment->name;
    }

    $databases = $acquiaApi->acquiaDatabases->getNames($appId);
    foreach ($databases as $database) {
      $this->say(sprintf('Gather database backup info for %s', $database->name));

      foreach ($environment_uuids as $environment_uuid => $name) {
        $backups = $this->safelyRunAcquiaRequest($acquiaApi, [
          $acquiaApi->acquiaDatabaseBackups,
          'getAll',
        ], $environment_uuid, $database->name);

        foreach ($backups as $backup) {
          $start_at = strtotime($backup->startedAt);

          if ($backup->type == 'ondemand' && time() - $start_at > $options['minimum-age']) {
            $this->say(sprintf('Deleting %s backup #%s on %s environment.', $database->name, $backup->id, $name));

            $this->safelyRunAcquiaRequest($acquiaApi, [
              $acquiaApi->acquiaDatabaseBackups,
              'delete',
            ], $environment_uuid, $database->name, $backup->id);
          }
        }
      }
    }
  }

  /**
   * Run an acquia api request, renew the token, and rerun if something fails.
   *
   * @param \Drupal\SwsDrush\Helpers\AcquiaApi $acquiaApi
   * @param callable $callable
   * @param ...$args
   *
   * @return mixed
   */
  protected function safelyRunAcquiaRequest(AcquiaApi $acquiaApi, callable $callable, ...$args): mixed {
    try {
      return $callable(...$args);
    }
    catch (\Exception $e) {
      $this->yell($e->getMessage(), 40, 'red');
      $acquiaApi->renewToken();
      return $callable(...$args);
    }
  }

  /**
   * Delete git branches and tags that are not currently deployed on Acquia.
   */
  #[CLI\Command(name: 'sws:acquia:clean-git')]
  #[CLI\Option(name: 'app-id', description: 'Acquia application ID')]
  #[CLI\Option(name: 'app-key', description: 'Acquia API key')]
  #[CLI\Option(name: 'app-secret', description: 'Acquia API secret')]
  public function cleanUnusedBranchesAndTags(array $options = [
    'app-id' => InputOption::VALUE_REQUIRED,
    'app-key' => InputOption::VALUE_REQUIRED,
    'app-secret' => InputOption::VALUE_REQUIRED,
  ]
  ): void {
    $acquiaApi = $this->getAcquiaApi();
    $appId = $this->input()->getOption('app-id');

    $active_branches = ['master', 'HEAD'];
    $active_tags = [];

    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $environment */
    foreach ($acquiaApi->acquiaEnvironments->getAll($appId) as $environment) {
      $git_url = $environment->vcs->url;

      $vcs = $environment->vcs->path;

      if (str_contains($vcs, 'tags/')) {
        $active_tags[] = str_replace('tags/', '', $vcs);
      }
      else {
        $active_branches[] = $vcs;
      }
    }

    $active_tags = array_unique($active_tags);
    $active_branches = array_unique($active_branches);

    $root = $this->getDir();
    if (file_exists("$root/deploy")) {
      $this->localMachineHelper()->execute([
        'git',
        'fetch',
      ], NULL, "$root/deploy");
    }
    else {
      $this->localMachineHelper()->execute([
        'git',
        'clone',
        $git_url,
        "$root/deploy",
      ], NULL, $this->getDir());
    }

    $result = $this->localMachineHelper()->execute([
      'git',
      'branch',
      '--remotes',
    ], NULL, "$root/deploy", FALSE);
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed getting branch names.', $result->getExitCode());
    }
    $branches = explode("\n", $result->getOutput());

    $remove_branches = [];
    foreach ($branches as $branch) {
      $branch = preg_replace('/ .*/', '', trim(str_replace('origin/', '', $branch)));

      if (!empty($active_branches) && !in_array($branch, $active_branches)) {
        $remove_branches[] = $branch;
      }
    }

    $result = $this->localMachineHelper()->execute([
      'git',
      'tag',
      '-l',
    ], NULL, "$root/deploy", FALSE);
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('Failed getting tag names.', $result->getExitCode());
    }

    $tags = explode("\n", $result->getOutput());
    $remove_tags = [];
    foreach ($tags as $tag) {
      $tag = trim($tag);
      if (!empty($active_tags) && !in_array($tag, $active_tags)) {
        $remove_tags[] = $tag;
      }
    }

    $perform_branch_delete = TRUE;
    $perform_tag_delete = TRUE;
    if ($this->input()->isInteractive()) {
      $perform_branch_delete = $this->confirm(sprintf('Are you sure you wish to delete the following branches? %s', implode(', ', $remove_branches)));
      $perform_tag_delete = $this->confirm(sprintf('Are you sure you wish to delete the following tags? %s', implode(', ', $remove_tags)));
    }
    if ($perform_branch_delete) {
      foreach (array_filter($remove_branches) as $branch) {
        $result = $this->localMachineHelper()->execute([
          'git',
          'push',
          '-d',
          'origin',
          $branch,
        ], NULL, "$root/deploy");
        if (!$result->isSuccessful()) {
          throw new CommandFailedException('Failed Deleting branch ' . $branch, $result->getExitCode());
        }
      }
    }
    if ($perform_tag_delete) {
      foreach (array_filter($remove_tags) as $tag) {
        $result = $this->localMachineHelper()->execute([
          'git',
          'push',
          'origin',
          ":refs/tags/$tag",
        ], NULL, "$root/deploy");
        if (!$result->isSuccessful()) {
          throw new CommandFailedException('Failed Deleting tag ' . $tag, $result->getExitCode());
        }
      }
    }
  }

}
