<?php

declare(strict_types=1);

namespace Drush\Commands;

use Consolidation\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Drush\Config\Loader\YamlConfigLoader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;

/**
 * Commands to assist with launching sites on the H&S platform.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HsLaunchDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Run commmands and set up configuration as part of a site launch.
   * 
   * @param string $site
   *   The machine name of the site.
   *
   * Replaces `blt humsci:launch-site`.
   */
  #[CLI\Command(name: 'humsci:launch-site')]
  #[CLI\Option(name: 'site', description: 'Site machine name to launch.')]
  public function launchSite(array $options = [
    'site' => InputOption::VALUE_REQUIRED,
  ]
  ){
    if (empty($options['site'])) {
      throw new \InvalidArgumentException('The --site option is required.');
    }

    // Genererate new domain and confirm with option to provide a different
    // domain.
    $newDomain = 'https://' . $this->getNewDomain($options['site']);
    $newDomain = $this->io()->ask('New domain?', $newDomain);

    // Get the production alias to execute on.
    $remoteAlias = $this->getSiteRemoteAlias($options['site']);
    
    // Turn off nobots.
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'sset',
      'nobots',
      0,
    ], NULL, $this->getDir());
    $this->logger()->notice('[LAUNCH] Set nobots state to 0.');

    // Enable domain redirect module and set the new domain.
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'cset',
      'domain_301_redirect.settings',
      'domain',
      $newDomain,
      '-y',
    ], NULL, $this->getDir());
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'cset',
      'domain_301_redirect.settings',
      'enabled',
      1,
      '-y',
    ], NULL, $this->getDir());
    $this->logger()->notice('[LAUNCH] Set domain_301_redirect to ' . $newDomain . ' and enabled redirect.');


    // Set the xml sitemap domain to the new domain.
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'sset',
      'xmlsitemap_base_url',
      $newDomain,
    ], NULL, $this->getDir());
    $this->logger()->notice('[LAUNCH] Set xmlsitemap_base_url state to ' . $newDomain . '.');

    // Rebuild the sitemap using drush command.
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'xmlsitemap:rebuild',
    ], NULL, $this->getDir());
    $this->logger()->notice('[LAUNCH] Rebuilt xmlsitemap.');
    
    // Rebuild the cache.
    $this->localMachineHelper()->execute([
      'drush',
      $remoteAlias,
      'cr',
    ], NULL, $this->getDir());
    $this->logger()->notice('[LAUNCH] Rebuilt cache.');
  }

  /**
   * Get the suggested new domain from the current drush alias.
   * 
   * @param string $site_name
   *   Drush machine name.
   * 
   * @return string
   *   Newly constructed domain.
   */
  protected function getNewDomain(string $site_name): string {
    $site_name = str_replace('_', '-', str_replace('__', '.', $site_name));
    return "$site_name.stanford.edu";
  }

  /**
   * Get the remote alias of a site using the sws.yml file in the site dir.
   *
   * @param string $siteName
   *   Site machine name.
   *
   * @return string
   *   Drush remote alias.
   */
  protected function getSiteRemoteAlias(string $siteName): string {
    // This was pulled from the same method in:
    //   drush/Commands/contrib/sws-drush-commands/src/Drush/Commands/SyncDrushCommands.php
    // Maybe move this to the SwsCommandsTrait?
    $fileSystem = $this->localMachineHelper()->getFilesystem();
    $configFile = Path::join($this->getDir(), 'docroot', 'sites', $siteName, 'sws.yml');
    $remoteAlias = "@$siteName.prod";

    if ($fileSystem->exists($configFile)) {
      $config = new Config();
      $loader = new YamlConfigLoader();
      $processor = new ConfigProcessor();
      $processor->extend($loader->load($configFile));
      $config->replace($processor->export());
      $ra = $config->get('site.remote-alias');
      if ($ra) {
        $remoteAlias = $ra;
      }
    }
    return str_starts_with($remoteAlias, '@') ? $remoteAlias : "@$remoteAlias";
  }
}
