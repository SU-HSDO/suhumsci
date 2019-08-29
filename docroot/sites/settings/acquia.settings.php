<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$group = EnvironmentDetector::getAhGroup();
$environment = EnvironmentDetector::getAhEnv();

// SimpleSAMLphp configuration
// Set the workgroup api cert paths.
$config['stanford_ssp.settings'] = [
  'workgroup_api_cert' => "/mnt/gfs/$group.$environment/nobackup/apikeys/saml/workgroup_api.cert",
  'workgroup_api_key' => "/mnt/gfs/$group.$environment/nobackup/apikeys/saml/workgroup_api.key",
];

switch (EnvironmentDetector::getAhEnv()) {
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
    $config['environment_indicator.indicator']['name'] = EnvironmentDetector::getAhEnv();
    break;
}

if (EnvironmentDetector::isProdEnv()) {
  $config['system.file']['path']['temporary'] = "/mnt/gfs/$group.$environment/tmp";
}
else {
  // Disables domain redirect on all environments except production.
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}

$settings['hash_salt'] = file_get_contents("/mnt/gfs/$group.$environment/nobackup/apikeys/salt.txt");
$settings['letsencrypt_challenge_directory'] = "/mnt/gfs/$group.$environment/files/";

// Don't lock config when using drush.
if (PHP_SAPI !== 'cli') {
  $settings['config_readonly'] = TRUE;
}
