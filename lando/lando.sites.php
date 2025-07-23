<?php
// @codingStandardsIgnoreFile
// This is required if you have localdev domains that are different from the
// url structure that's already added to $sites in sites.php
$settings = glob(__DIR__ . '/*/settings.php');
foreach ($settings as $settings_file) {
  $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
  $site_dir = str_replace('/settings.php', '', $site_dir);
  if ($site_dir == 'default') {
    $site_dir = 'swshumsci';
  }
  $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));
  $sites["$sitename.suhumsci.loc"] = $site_dir; // Do we need to add more things to our sites array, to get requests to reach our multisites?
}
