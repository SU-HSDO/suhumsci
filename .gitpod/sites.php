<?php

$settings = glob(__DIR__ . '/*/settings.php');

$workspace_url = getenv('GITPOD_WORKSPACE_URL');
$workspace_domain = preg_replace('/^.*?\/\//', '', $workspace_url);

// For each directory with a settings.php file, create possible combinations
// of the urls for that directory. A single underscore `_` in the direcotry name
// represents a dash `-` in the url. A double underscore represents a period `.`
// in the url. Using this standard we can easily keep track of what urls is for
// each site directory.
foreach ($settings as $settings_file) {
  $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
  $site_dir = str_replace('/settings.php', '', $site_dir);

  if ($site_dir == 'default') {
    $site_dir = 'swshumsci';
  }

  $sites["$port-$workspace_domain"] = $site_dir;
}