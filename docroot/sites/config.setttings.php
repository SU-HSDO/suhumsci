<?php

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
  'mail_attr' => 'eduPersonPrincipalName',
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
    'population' => 'administrator:eduPersonEntitlement,=,hsdo:web|administrator:eduPersonEntitlement,=,itservices:webservices',
    'eval_every_time' => TRUE,
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

if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  if (PHP_SAPI !== 'cli') {
    // Don't lock config when using drush.
    $settings['config_readonly'] = TRUE;
  }
}

// Lets whitelist everything because in our event subscriber we have the
// ability to decide which forms are locked.
// @see \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
$settings['config_readonly_whitelist_patterns'] = ['*'];
