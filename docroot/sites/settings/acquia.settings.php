<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$group = EnvironmentDetector::getAhGroup();
$environment = EnvironmentDetector::getAhEnv();

// Set the temp directory as per https://docs.acquia.com/acquia-cloud/manage/files/broken/
$settings['file_temp_path'] = '/mnt/gfs/' . EnvironmentDetector::getAhGroup() . '.' . EnvironmentDetector::getAhEnv() . '/tmp';
$settings['letsencrypt_challenge_directory'] = $settings['file_temp_path'];

if (!EnvironmentDetector::isProdEnv()) {
  // Disables domain redirect on all environments except production.
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}

