<?php

require DRUPAL_ROOT . "/sites/default/settings.php";

if (file_exists('/var/www/site-php')) {
  require '/var/www/site-php/swshumsci/archaeology-settings.inc';
}

// Set sync directory for production environment.
if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  $config_directories['sync'] = "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/" . basename(__DIR__) . "/config";
}
require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";
