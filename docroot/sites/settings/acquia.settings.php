<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$group = EnvironmentDetector::getAhGroup();
$environment = EnvironmentDetector::getAhEnv();

// Set the temp directory as per https://docs.acquia.com/acquia-cloud/manage/files/broken/
$settings['file_temp_path'] = '/mnt/gfs/' . EnvironmentDetector::getAhGroup() . '.' . EnvironmentDetector::getAhEnv() . '/tmp';

