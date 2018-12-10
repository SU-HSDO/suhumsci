<?php

/**
 * @file
 * This file gets added by blt.settings.php as an override configuration.
 */

use Drupal\Core\Serialization\Yaml;

// SimpleSAMLphp configuration
// Provide universal absolute path to the installation.
if (isset($_ENV['AH_SITE_NAME']) && is_dir('/var/www/html/' . $_ENV['AH_SITE_NAME'] . '/simplesamlphp')) {
  $settings['simplesamlphp_dir'] = '/var/www/html/' . $_ENV['AH_SITE_NAME'] . '/simplesamlphp';
}
else {
  // Local SAML path.
  if (is_dir(DRUPAL_ROOT . '/../simplesamlphp')) {
    $settings['simplesamlphp_dir'] = DRUPAL_ROOT . '/../simplesamlphp';
  }
}

if (isset($_ENV) && isset($_ENV['AH_SITE_GROUP']) && isset($_ENV['AH_SITE_ENVIRONMENT']) && $_ENV['AH_SITE_ENVIRONMENT'] == 'prod') {
  $config['system.file']['path']['temporary'] = "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/tmp";
}
else {
  $config['system.file']['path']['temporary'] = sys_get_temp_dir();
}

$config['simplesamlphp_auth.settings'] = [
  'langcode' => 'en',
  'default_langcode' => 'en',
  'activate' => TRUE,
  'mail_attr' => 'mail',
  'unique_id' => 'uid',
  'user_name' => 'displayName',
  'auth_source' => 'default-sp',
  'login_link_display_name' => 'Stanford Login',
  'header_no_cache' => TRUE,
  'user_register_original' => 'visitors',
  'register_users' => TRUE,
  'autoenablesaml' => TRUE,
  'debug' => FALSE,
  'secure' => FALSE,
  'httponly' => FALSE,
  'role' => [
    //    'population' => 'administrator:eduPersonEntitlement,=,hsdo:web|administrator:eduPersonEntitlement,=,itservices:webservices',
    'eval_every_time' => 2,
  ],
  'allow' => [
    'set_drupal_pwd' => FALSE,
    'default_login' => TRUE,
  ],
  'sync' => [
    'mail' => TRUE,
    'user_name' => TRUE,
  ],
];

$config['environment_indicator.indicator']['bg_color'] = '#086601';
$config['environment_indicator.indicator']['fg_color'] = '#fff';
$config['environment_indicator.indicator']['name'] = 'Local';

if (isset($_ENV) && isset($_ENV['AH_SITE_GROUP']) && isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  switch ($_ENV['AH_SITE_ENVIRONMENT']) {
    case 'dev':
      $config['environment_indicator.indicator']['bg_color'] = '#6B0500';
      $config['environment_indicator.indicator']['fg_color'] = '#fff';
      $config['environment_indicator.indicator']['name'] = 'Development';
      break;
    case 'test':
      $config['environment_indicator.indicator']['bg_color'] = '#4127C2';
      $config['environment_indicator.indicator']['fg_color'] = '#fff';
      $config['environment_indicator.indicator']['name'] = 'Staging';
      break;
    case 'prod':
      $config['environment_indicator.indicator']['bg_color'] = '#000';
      $config['environment_indicator.indicator']['fg_color'] = '#fff';
      $config['environment_indicator.indicator']['name'] = 'Production';
      break;
    default:
      $config['environment_indicator.indicator']['bg_color'] = '#086601';
      $config['environment_indicator.indicator']['fg_color'] = '#fff';
      $config['environment_indicator.indicator']['name'] = $_ENV['AH_SITE_ENVIRONMENT'];
      break;
  }
}

if ($is_ah_env && PHP_SAPI !== 'cli') {
  // Don't lock config when using drush.
  $settings['config_readonly'] = TRUE;
}

// Lets whitelist everything because in our event subscriber we have the
// ability to decide which forms are locked.
// @see \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
$settings['config_readonly_whitelist_patterns'] = ['*'];

// On acquia, load a salt from the server.
if ($is_ah_env) {
  $settings['hash_salt'] = file_get_contents("/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/nobackup/apikeys/salt.txt");
  $settings['letsencrypt_challenge_directory'] = "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/files/";
}

// Set the config_ignore settings so that config imports will function on local.
if ($is_local_env) {
  $config_ignore = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/envs/local/config_ignore.settings.yml'));
  $config['config_ignore.settings']['ignored_config_entities'] = $config_ignore['ignored_config_entities'];
}

// Disables domain redirect on all environments except production.
if (!$is_ah_prod_env) {
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}
