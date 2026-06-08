<?php

/**
 * @file
 * Global settings.
 */

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

// When the encryption environment variable is not provided (local/ci/etc),
// fake the encryption string so that the site doesn't break.
if (!getenv('REAL_AES_ENCRYPTION')) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $randomString = '';
  for ($i = 0; $i < 256 / 8; $i++) {
    $randomString .= $characters[rand(0, strlen($characters) - 1)];
  }
  putenv("REAL_AES_ENCRYPTION=$randomString");
}

// Default config sync directory.
$settings['config_sync_directory'] = EnvironmentDetector::getRepoRoot() . '/config/default';

// Include services.yml file for all sites.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/services.yml';

/**
 * Include settings files in docroot/sites/settings.
 *
 * If instead you want to add settings to a specific site, see includes
 * file in docroot/sites/{site-name}/settings/default.includes.settings.php.
 */
$additionalSettingsFiles = [
  __DIR__ . '/saml.settings.php',
  __DIR__ . '/environment_indicator.php',
  __DIR__ . '/local.settings.php',
  __DIR__ . '/fast404.settings.php',
  DRUPAL_ROOT . '/../keys/secrets.settings.php',
];

// Include Codespaces settings when in a Codespaces environment.
if (getenv('CODESPACES')) {
  $additionalSettingsFiles[] = __DIR__ . '/codespaces.settings.php';
}

foreach ($additionalSettingsFiles as $settingsFile) {
  if (file_exists($settingsFile)) {
    // phpcs:ignore
    require $settingsFile;
  }
}

// Lets whitelist everything because in our event subscriber we have the
// ability to decide which forms are locked.
// @see \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
$settings['config_readonly_whitelist_patterns'] = ['*'];

// Don't lock config when using drush.
if (PHP_SAPI !== 'cli' && EnvironmentDetector::isProdEnv()) {
  $settings['config_readonly'] = TRUE;
}

// Enable nobots on any non-prod site.
if (!EnvironmentDetector::isProdEnv()) {
  $settings['nobots'] = TRUE;
  $config['google_analytics.settings']['account'] = '';
}

if (EnvironmentDetector::isAhEnv()) {
  require 'acquia.settings.php';
}
else {
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
  $config['mail_safety.settings']['enabled'] = TRUE;
  $config['mail_safety.settings']['send_mail_to_dashboard'] = TRUE;
}

$siteimprove_api_key = getenv('SITEIMPROVE_API_KEY', TRUE) ?: getenv('SITEIMPROVE_API_KEY');
$siteimprove_username = getenv('SITEIMPROVE_USERNAME', TRUE) ?: getenv('SITEIMPROVE_USERNAME');

if ($siteimprove_api_key && $siteimprove_username) {
  // Set the SiteImprove API key and username.
  $config['hs_siteimprove.settings'] = [
    'api_key' => $siteimprove_api_key,
    'username' => $siteimprove_username,
  ];
}

// Translation overrides to replace Drupalisms with more user-friendly terms.
$settings['locale_custom_strings_en'][''] = [
  'Entityqueues' => 'Listing',
  'Edit Entity Queue' => 'Edit listing',
  'Edit subqueue %label' => 'Edit listing %label',
  'The entity subqueue %label has been added.' => 'The listing %label has been added.',
  'The entity subqueue %label has been updated.' => 'The listing %label has been updated.',
];

// During partial imports allow these configurations to be deleted. Currently
// this is only a prefix string match with no wildcards and it will only match
// the beginning of the config name. The primary use-case for this is to allow
// configuration related to modules that are being uninstalled during a site
// sync to be deleted during the import process.
$settings['hs_config_partial_allow_delete'] = [
  'acquia_connector.',
  'purge.',
  'purge_queuer_coretags.',
  'ultimate_cron.job.',
];
