<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

// @codingStandardsIgnoreFile

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 *
 * This file is required for multi-site support and also allows you to define a
 * set of aliases that map hostnames, ports, and pathnames to configuration
 * directories in the sites directory. These aliases are loaded prior to
 * scanning for directories, and they are exempt from the normal discovery
 * rules. See default.settings.php to view how Drupal discovers the
 * configuration directory when no alias is found.
 *
 * Aliases are useful on development servers, where the domain name may not be
 * the same as the domain of the live server. Since Drupal stores file paths in
 * the database (files, system table, etc.) this will ensure the paths are
 * correct when the site is deployed to a live server.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/sites.php'.
 *
 * Aliases are defined in an associative array named $sites. The array is
 * written in the format: '<port>.<domain>.<path>' => 'directory'. As an
 * example, to map https://www.drupal.org:8080/mysite/test to the configuration
 * directory sites/example.com, the array should be defined as:
 * @code
 * $sites = [
 *   '8080.www.drupal.org.mysite.test' => 'example.com',
 * ];
 * @endcode
 * The URL, https://www.drupal.org:8080/mysite/test/, could be a symbolic link
 * or an Apache Alias directive that points to the Drupal root containing
 * index.php. An alias could also be created for a subdomain. See the
 * @link https://www.drupal.org/documentation/install online Drupal installation guide @endlink
 * for more information on setting up domains, subdomains, and subdirectories.
 *
 * The following examples look for a site configuration in sites/example.com:
 * @code
 * URL: http://dev.drupal.org
 * $sites['dev.drupal.org'] = 'example.com';
 *
 * URL: http://localhost/example
 * $sites['localhost.example'] = 'example.com';
 *
 * URL: http://localhost:8080/example
 * $sites['8080.localhost.example'] = 'example.com';
 *
 * URL: https://www.drupal.org:8080/mysite/test/
 * $sites['8080.www.drupal.org.mysite.test'] = 'example.com';
 * @endcode
 *
 * @see default.settings.php
 * @see \Drupal\Core\DrupalKernel::getSitePath()
 * @see https://www.drupal.org/documentation/install/multi-site
 */

$settings = glob(__DIR__ . '/*/settings.php');

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

  $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));
  $sites[$sitename] = $site_dir;
  $sites["$sitename.stanford.edu"] = $site_dir;

  $sitename = explode('.', $sitename);

  foreach (['-dev', '-stage', '-prod'] as $environment) {
    $environment_sitename = $sitename;
    $environment_sitename[0] .= $environment;
    $sites[implode('.', $environment_sitename) . '.stanford.edu'] = $site_dir;
  }
}

// Manually point URL's that don't match their site paths.
$sites['iranian-studies.stanford.edu'] = 'iranianstudies';
$sites['mrc.stanford.edu'] = 'mrc2021';
$sites['gus-humsci.stanford.edu'] = 'gus_humsci2021';
$sites['dfetter.humsci.stanford.edu'] = 'dfetter2022__humsci';
$sites['heidi-williams.humsci.stanford.edu'] = 'heidi_williams2022__humsci';
$sites['gavin-wright.humsci.stanford.edu'] = 'gavin_wright2022__humsci';
$sites['humanitiescore.stanford.edu'] = 'humanitiescore2022';
$sites['facultyaffairs-humsci.stanford.edu'] = 'facultyaffairs_humsci2021';
$sites['lowe.stanford.edu'] = 'lowe2022';
$sites['shenlab.stanford.edu'] = 'shenlab2022';
$sites['duboislab.stanford.edu'] = 'duboislab2022';
$sites['francestanford.stanford.edu'] = 'francestanford2022';
$sites['hsweb-userguide-traditional.stanford.edu'] = 'swshumsci_sandbox';
$sites['researchadmin-humsci.stanford.edu'] = 'researchadmin_humsci2022';
$sites['insidehs.stanford.edu'] = 'insidehs2023';
$sites['popstudies.stanford.edu'] = 'popstudies2023';


if (file_exists(__DIR__ . '/local.sites.php')) {
  require __DIR__ . '/local.sites.php';
}

// Include sites file for CI environments.
if (EnvironmentDetector::isCiEnv()) {
  require __DIR__ . '/ci.sites.php';
}

// Include sites file from the Acquia environment.
if (EnvironmentDetector::isAhEnv() && file_exists(EnvironmentDetector::getAhFilesRoot() . '/sites.php')) {
  require EnvironmentDetector::getAhFilesRoot() . '/sites.php';
}
