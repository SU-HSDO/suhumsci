<?php

/**
 * @file
 * Codespaces-specific Drupal settings.
 *
 * This file is included by sws.settings.php when the CODESPACE_NAME
 * environment variable is present (automatically set by GitHub Codespaces).
 */

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

// Load or generate encryption key for Codespaces.
if (!getenv('REAL_AES_ENCRYPTION')) {
  $codespaces_key_file = '/workspaces/.codespace-keys/encryption.key';
  if (file_exists($codespaces_key_file)) {
    putenv("REAL_AES_ENCRYPTION=" . trim(file_get_contents($codespaces_key_file)));
  }
  else {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 256 / 8; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    putenv("REAL_AES_ENCRYPTION=$randomString");
  }
}

// Database configuration for Codespaces.
$databases = [
  'default' => [
    'default' => [
      'database' => 'drupal',
      'username' => 'drupal',
      'password' => 'drupal',
      'host' => 'mysql',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
      'prefix' => '',
    ],
  ],
];

// Use development service parameters.
$settings['container_yamls'][] = EnvironmentDetector::getRepoRoot() . '/docroot/sites/development.services.yml';

// Allow access to update.php.
$settings['update_free_access'] = TRUE;

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Skip file system permissions hardening.
$settings['skip_permissions_hardening'] = TRUE;

// Files paths.
$settings['file_private_path'] = EnvironmentDetector::getRepoRoot() . '/files-private/default';
// phpcs:ignore
$settings['file_public_path'] = 'sites/' . EnvironmentDetector::getSiteName($site_path) . '/files';

// Trusted host configuration - allow all in Codespaces (development only).
$settings['trusted_host_patterns'] = [
  '^.+$',
];

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Hide local login in Codespaces.
$config['stanford_samlauth.settings']['hide_local_login'] = FALSE;
