<?php

/**
 * @file
 * Environment indicator configuration.
 * @see https://www.drupal.org/project/environment_indicator
 * @see /admin/config/development/environment-indicator
 */

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

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
    $envs = array_filter(EnvironmentDetector::getEnvironments());
    $env = key($envs) ?? 'Local';
    $config['environment_indicator.indicator']['bg_color'] = '#086601';
    $config['environment_indicator.indicator']['fg_color'] = '#fff';
    $config['environment_indicator.indicator']['name'] = $env;
    break;
}
