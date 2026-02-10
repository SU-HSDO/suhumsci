<?php
// @codingStandardsIgnoreFile
// DDEV multisite configuration for suhumsci
// This file maps domain names to site directories

$sites = [];

// Get all site directories
$site_dirs = glob(__DIR__ . "/*", GLOB_ONLYDIR);
foreach ($site_dirs as $site_dir) {
  $site_name = basename($site_dir);
  
  // Skip certain directories
  if (in_array($site_name, ["default", "all", "example.sites.php"])) {
    continue;
  }
  
  // Convert site name to domain format
  // Replace underscores with hyphens and double underscores with dots
  $domain = str_replace("_", "-", str_replace("__", ".", $site_name));
  
  // Map to DDEV domain format.
  // Example: hs-traditional.ddev.site => hs_traditional.
  $sites["$domain.ddev.site"] = $site_name;

  // Create an internal mapping for drush commands.
  // Example: hs-traditional => hs_traditional.
  $sites[$domain] = $site_name;
}

// Default site mapping
$sites["suhumsci.ddev.site"] = "default";
