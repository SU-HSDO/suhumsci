<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$group = EnvironmentDetector::getAhGroup();
$environment = EnvironmentDetector::getAhEnv();

$settings['file_temp_path'] = "/mnt/tmp/$group.$environment";

if (!EnvironmentDetector::isProdEnv()) {
  // Disables domain redirect on all environments except production.
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}

$settings['letsencrypt_challenge_directory'] = "/mnt/gfs/$group.$environment/files/";
