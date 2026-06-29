<?php

declare(strict_types=1);

namespace Drush\Commands;

use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;

/**
 * Commands for generating reports across the H&S multisite platform.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HsReportsDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Report users assigned to a given role across all sites.
   *
   * Replaces `blt humsci:role-report`.
   */
  #[CLI\Command(name: 'humsci:reports:users-by-role')]
  #[CLI\Argument(name: 'role', description: 'Machine name of the Drupal role to report on.')]
  #[CLI\Option(name: 'env', description: 'Environment to query: stage (default), prod, or dev.')]
  #[CLI\Option(name: 'format', description: 'Output format: csv (default) or json.')]
  #[CLI\Option(name: 'include-blocked', description: 'Include blocked users. Adds a Status column to the output.')]
  #[CLI\Option(name: 'omit-empty', description: 'Omit sites with no matching users from the output.')]
  #[CLI\Usage(name: 'drush humsci:reports:users-by-role site_manager --env=prod > report.csv', description: 'CSV report of active site_manager users on prod saved to file.')]
  #[CLI\Usage(name: 'drush humsci:reports:users-by-role site_manager --env=prod --include-blocked', description: 'Include blocked users with a Status column.')]
  #[CLI\Usage(name: 'drush humsci:reports:users-by-role site_manager --env=prod --omit-empty', description: 'Skip sites with no matching users.')]
  #[CLI\Usage(name: 'drush humsci:reports:users-by-role site_manager --env=prod --format=json', description: 'JSON report on prod.')]
  public function usersByRole(string $role, array $options = [
    'env' => 'stage',
    'format' => 'csv',
    'include-blocked' => FALSE,
    'omit-empty' => FALSE,
  ]): void {
    $env = $options['env'];
    $format = $options['format'];
    $includeBlocked = (bool) $options['include-blocked'];
    $omitEmpty = (bool) $options['omit-empty'];

    if (!in_array($env, ['stage', 'prod', 'dev'], TRUE)) {
      throw new \InvalidArgumentException(dt('Invalid environment "!env". Allowed values: stage, prod, dev.', ['!env' => $env]));
    }

    if (!in_array($format, ['csv', 'json'], TRUE)) {
      throw new \InvalidArgumentException(dt('Invalid format "!format". Allowed values: csv, json.', ['!format' => $format]));
    }

    if (!preg_match('/^[a-z0-9_]+$/', $role)) {
      throw new \InvalidArgumentException(dt('Role "!role" is not a valid machine name.', ['!role' => $role]));
    }

    $multisites = $this->getConfig()->get('command.sws.options.multisites') ?? ['default'];

    $select = $includeBlocked ? 'd.name, d.mail, d.status' : 'd.name, d.mail';
    $statusClause = $includeBlocked ? '' : 'AND d.status = 1';
    $sql = trim("SELECT $select FROM users_field_data d INNER JOIN user__roles r ON d.uid = r.entity_id WHERE r.roles_target_id = '$role' $statusClause");

    $out = NULL;
    $jsonData = [];

    if ($format === 'csv') {
      $out = fopen('php://stdout', 'w');
      $headers = ['Site', 'URL', 'Role', 'Name', 'Email'];
      if ($includeBlocked) {
        $headers[] = 'Status';
      }
      fputcsv($out, $headers);
    }

    foreach ($multisites as $site) {
      $alias = "@{$site}.{$env}";
      $url = $this->getSiteUrl($site, $env);

      $result = $this->localMachineHelper()->execute(
        ['drush', $alias, 'sqlq', $sql],
        NULL,
        $this->getDir(),
        FALSE
      );

      if (!$result->isSuccessful()) {
        $this->logger()->warning(dt('Failed to query "!alias", skipping.', ['!alias' => $alias]));
        continue;
      }

      $lines = array_values(array_filter(explode("\n", trim($result->getOutput()))));

      $users = [];
      foreach ($lines as $line) {
        $cols = explode("\t", $line);
        $user = [
          'name' => $cols[0] ?? '',
          'email' => $cols[1] ?? '',
        ];
        if ($includeBlocked) {
          $user['status'] = isset($cols[2]) ? ($cols[2] === '1' ? 'Active' : 'Blocked') : '';
        }
        $users[] = $user;
      }

      if ($format === 'json') {
        if (empty($users) && $omitEmpty) {
          continue;
        }
        $jsonData[] = [
          'role' => $role,
          'site' => $site,
          'url' => $url,
          'users' => $users,
        ];
        continue;
      }

      if (empty($users)) {
        if ($omitEmpty) {
          continue;
        }
        $row = [$site, $url, $role, '(No users found)', ''];
        if ($includeBlocked) {
          $row[] = '';
        }
        fputcsv($out, $row);
        continue;
      }

      foreach ($users as $user) {
        $row = [$site, $url, $role, $user['name'], $user['email']];
        if ($includeBlocked) {
          $row[] = $user['status'];
        }
        fputcsv($out, $row);
      }
    }

    if ($format === 'csv') {
      fclose($out);
      return;
    }

    $this->output()->writeln(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

  /**
   * Build the site URL for a given environment from the multisite machine name.
   *
   * Applies the same substitution documented in docroot/sites/sites.php:
   * double underscores become dots, single underscores become dashes.
   */
  protected function getSiteUrl(string $site, string $env): string {
    $parts = explode('.', str_replace('_', '-', str_replace('__', '.', $site)));
    $parts[0] .= '-' . $env;
    return 'https://' . implode('.', $parts) . '.stanford.edu';
  }

}
