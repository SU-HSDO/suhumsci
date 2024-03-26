<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$group = EnvironmentDetector::getAhGroup();
$environment = EnvironmentDetector::getAhEnv();

// Set the temp directory as per https://docs.acquia.com/acquia-cloud/manage/files/broken/
$settings['file_temp_path'] = '/mnt/gfs/' . EnvironmentDetector::getAhGroup() . '.' . EnvironmentDetector::getAhEnv() . '/tmp';
$settings['letsencrypt_challenge_directory'] = $settings['file_temp_path'];

// Disables domain redirect on all environments except production.
$config['domain_301_redirect.settings']['enabled'] = EnvironmentDetector::isProdEnv();

// Enable shield on non prod.
$config['shield.settings']['shield_enable'] = !EnvironmentDetector::isProdEnv();
