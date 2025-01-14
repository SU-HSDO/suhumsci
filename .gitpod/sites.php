<?php

$settings = glob(__DIR__ . '/*/settings.php');

$workspace_url = getenv('GITPOD_WORKSPACE_URL');
$workspace_domain = preg_replace('/^.*?\/\//', '', $workspace_url);

$port = 8002;
foreach ($settings as $settings_file) {
  $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
  $site_dir = str_replace('/settings.php', '', $site_dir);

  if ($site_dir == 'default') {
    $site_dir = 'swshumsci';
  }

  $sites["$port-$workspace_domain"] = $site_dir;
  $port++;
}
