<?php

// phpcs:ignoreFile

if ($tugboat_token = getenv('TUGBOAT_SERVICE_TOKEN')) {

  $settings = glob(__DIR__ . '/*/settings.php');

  foreach ($settings as $settings_file) {
    $site_dir = basename(dirname($settings_file));
    if ($site_dir == 'default') {
      $site_dir = 'swshumsci';
    }

    $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));
    $sites["$sitename-$tugboat_token.tugboatqa.com"] = $site_dir;
  }
  $sites[getenv('TUGBOAT_DEFAULT_SERVICE_URL_HOST')] = 'hs_colorful';
}
