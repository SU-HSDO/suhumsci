<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Maps local development domains to multisite directories.
 *
 * Supports both *.suhumsci.lndo.site (Lando wildcard proxy)
 * and *.suhumsci.loc (legacy /etc/hosts approach).
 */

$settings = glob(__DIR__ . '/*/settings.php');
foreach ($settings as $settings_file) {
  $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
  $site_dir = str_replace('/settings.php', '', $site_dir);

  if ($site_dir == 'default') {
    $site_dir = 'swshumsci';
  }

  // Convert directory name to domain-safe name:
  // underscores -> dashes, double underscores -> periods.
  $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));

  // Lando wildcard proxy domains (preferred - no /etc/hosts needed).
  $sites["$sitename.suhumsci.lndo.site"] = $site_dir;

  // Legacy .loc domains (requires /etc/hosts entries).
  $sites["$sitename.suhumsci.loc"] = $site_dir;
}
