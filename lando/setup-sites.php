#!/usr/bin/env php
<?php

/**
 * @file
 * Lando build script: patches local.settings.php files for Lando compatibility.
 *
 * Replaces the fragile sed-based approach that breaks on VirtioFS.
 * Run after `blt blt:init:settings` to update database credentials.
 */

$sites_dir = '/app/docroot/sites';
$files = glob($sites_dir . '/*/settings/local.settings.php');

if (empty($files)) {
  echo "No local.settings.php files found. Ensure blt:init:settings has run.\n";
  exit(0);
}

$replacements = [
  // Database credentials.
  "'username' => 'root'" => "'username' => 'drupal'",
  "'password' => 'password'" => "'password' => 'drupal'",
  "'host' => 'localhost'" => "'host' => 'database'",
  // Update deprecated database driver namespace for Drupal 10+.
  // Two patterns needed: the 4-backslash version matches the escaped form
  // in PHP source (e.g. double-quoted strings), while the 2-backslash version
  // matches the literal namespace in single-quoted strings.
  "Drupal\\\\Core\\\\Database\\\\Driver\\\\mysql" => "Drupal\\\\mysql\\\\Driver\\\\Database\\\\mysql",
  "'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql'" => "'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql'",
];

$count = 0;
foreach ($files as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    echo "Warning: Could not read $file\n";
    continue;
  }

  $updated = str_replace(
    array_keys($replacements),
    array_values($replacements),
    $content
  );

  if ($updated !== $content) {
    if (file_put_contents($file, $updated) !== false) {
      $count++;
    } else {
      echo "Warning: Could not write $file\n";
    }
  }
}

echo "Updated $count local.settings.php file(s) for Lando.\n";
