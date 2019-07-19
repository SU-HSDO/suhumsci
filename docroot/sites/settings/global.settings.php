<?php

/**
 * @file
 * This file gets added by blt.settings.php as an override configuration.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;
use Drupal\Core\Serialization\Yaml;

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

// Local SAML path.
if (is_dir(DRUPAL_ROOT . '/../simplesamlphp')) {
  $settings['simplesamlphp_dir'] = DRUPAL_ROOT . '/../simplesamlphp';
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

// Lets whitelist everything because in our event subscriber we have the
// ability to decide which forms are locked.
// @see \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
$settings['config_readonly_whitelist_patterns'] = ['*'];

$config['system.file']['path']['temporary'] = sys_get_temp_dir();

// Set the config_ignore settings so that config imports will function on local.
if (EnvironmentDetector::isLocalEnv()) {
  $config_ignore = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/envs/local/config_ignore.settings.yml'));
  $config['config_ignore.settings']['ignored_config_entities'] = $config_ignore['ignored_config_entities'];
}

if (EnvironmentDetector::isAhEnv()) {
  require 'acquia.settings.php';
}
else {
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}
