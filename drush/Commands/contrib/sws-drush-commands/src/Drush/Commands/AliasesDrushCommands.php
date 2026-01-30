<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use AcquiaCloudApi\Response\ApplicationResponse;
use Drupal\SwsDrush\Helpers\AcquiaApi;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class AliasesDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  protected string $appId;

  protected string $aliasDir;

  protected AcquiaApi $acquiaApi;

  /**
   * Build Drush Aliases.
   *
   * Replaces `blt aliases`.
   */
  #[CLI\Command(name: 'sws:acquia:alias-build', aliases: ['aliases', 'sab'])]
  #[CLI\Option(name: 'app-id', description: 'Acquia application ID')]
  #[CLI\Option(name: 'app-key', description: 'Acquia API key')]
  #[CLI\Option(name: 'app-secret', description: 'Acquia API secret')]
  #[CLI\Option(name: 'alias-dir', description: 'Directory for alias files')]
  #[CLI\Usage(name: 'drush aliases --app-id=foo --app-key=bar --app-secret=baz --alias-dir=drush/sites', description: 'Build all site aliases into drush/sites directory.')]
  public function buildAliases($options = [
    'app-id' => InputOption::VALUE_REQUIRED,
    'app-key' => InputOption::VALUE_REQUIRED,
    'app-secret' => InputOption::VALUE_REQUIRED,
    'alias-dir' => 'drush/sites',
  ]
  ) {
    /** @var \Drush\Boot\BootstrapManager $bootstrap */
    $bootstrap = Drush::bootstrapManager();
    $this->aliasDir = $this->input()
      ->getOption('alias-dir') ?: Path::join($bootstrap->getComposerRoot(), 'drush', 'sites');

    $this->acquiaApi = $this->getAcquiaApi();
    $this->appId = $this->input()->getOption('app-id');
    $site = $this->acquiaApi->acquiaApplications->get($this->appId);

    $this->appId = $this->input()->getOption('app-id');

    // Build alias files.
    $this->getSiteAliases($site);
  }

  /**
   * Gets generated drush site aliases.
   *
   * @param \AcquiaCloudApi\Response\ApplicationResponse $site
   *   The Acquia subscription that aliases will be generated for.
   *
   * @throws \Exception
   */
  protected function getSiteAliases(ApplicationResponse $site) {
    $sites = [];
    $this->io()
      ->writeln("<info>Gathering sites list from Acquia Cloud.</info>");

    $environments = $this->acquiaApi->acquiaEnvironments->getAll($this->appId);
    $hosting = $site->hosting->type;
    $site_split = explode(':', $site->hosting->id);

    foreach ($environments as $env) {
      $environment_servers = $this->acquiaApi->acquiaServers->getAll($env->uuid);
      $web_servers = array_filter($environment_servers->getArrayCopy(), function($server) {
        return in_array('web', $server->roles);
      });

      $domains = $env->domains;
      $this->say('<info>Found ' . count($domains) . ' domains for environment ' . $env->name . ', writing aliases...</info>');

      $sshFull = $env->sshUrl;
      $ssh_split = explode('@', $env->sshUrl);
      $envName = $env->name;
      $remoteHost = $ssh_split[1];
      $remoteUser = $ssh_split[0];

      if (in_array($hosting, ['ace', 'acp'])) {
        $siteID = $site_split[1];
        $uri = $env->domains[0];
        $sites[$siteID][$envName] = ['uri' => $uri];
        $siteAlias = $this->getAliases($uri, $envName, $remoteHost, $remoteUser);
        $sites[$siteID][$envName] = $siteAlias[$envName];
      }

      if ($hosting == 'acsf') {
        $this->say('<info>ACSF project detected - generating sites data....</info>');

        $acsf_sites = [];
        try {
          $acsf_sites = $this->getSitesJson($sshFull, $remoteUser);
        }
        catch (\Exception $e) {
          $this->logger->error("Could not fetch ACSF data for $envName. Error: " . $e->getMessage());
        }

        // Look for list of sites and loop over it.
        if ($acsf_sites) {
          $server_key = 0;
          foreach ($acsf_sites['sites'] as $name => $info) {
            // Reset uri value to identify non-primary domains.
            $uri = NULL;
            $siteID = NULL;

            // Get site prefix from main domain.
            if (strpos($name, '.acsitefactory.com')) {
              $acsf_site_name = explode('.', $name, 2);
              $siteID = $acsf_site_name[0];
            }
            if (!empty($siteID)) {
              $uri = $name;
            }

            // Skip sites without primary domain as the alias will be invalid.
            if (isset($uri)) {
              // Pick a web server to use as the host.
              $server_key = isset($web_servers[$server_key]) ? $server_key : key($web_servers);
              $server = $web_servers[$server_key];
              $server_key++;

              $sites[$siteID][$envName] = ['uri' => $uri];
              $siteAlias = $this->getAliases($uri, $envName, $server->hostname, $remoteUser, $siteID);
              $sites[$siteID][$envName] = $siteAlias[$envName];
            }
          }
        }
      }
    }

    // Write the alias files to disk.
    foreach ($sites as $siteID => $aliases) {
      $this->writeSiteAliases($siteID, $aliases);
    }
  }

  /**
   * Generates a site alias for valid domains.
   *
   * @param string $uri
   *   The unique site url.
   * @param string $envName
   *   The current environment.
   * @param string $remoteHost
   *   The remote host.
   * @param string $remoteUser
   *   The remote user.
   *
   * @return array|FALSE
   *   The full alias for this site, FALSE if skipped.
   */
  protected function getAliases(string $uri, string $envName, string $remoteHost, string $remoteUser): array|false {
    $alias = [];
    // Skip wildcard domains.
    $skip_site = FALSE;
    if (str_contains($uri, ':*')) {
      $skip_site = TRUE;
    }

    if (!$skip_site) {
      $docroot = '/var/www/html/' . $remoteUser . '/docroot';
      $alias[$envName]['uri'] = $uri;
      $alias[$envName]['host'] = $remoteHost;
      $alias[$envName]['options'] = [];
      $alias[$envName]['paths'] = ['dump-dir' => '/mnt/tmp'];
      $alias[$envName]['root'] = $docroot;
      $alias[$envName]['user'] = $remoteUser;
      $alias[$envName]['ssh'] = ['options' => '-p 22', 'tty' => 0];
      $alias[$envName]['env-vars']['WEBHEAD'] = substr($remoteHost, 0, strpos($remoteHost, '.'));

      return $alias;
    }
    return FALSE;
  }

  /**
   * Gets ACSF sites info without secondary API calls or Drupal bootstrap.
   *
   * @param string $sshFull
   *   The full ssh connection string for this environment.
   * @param string $remoteUser
   *   The site.env remoteUser string used in the remote private files path.
   *
   * @return array
   *   An array of ACSF site data for the current environment.
   */
  protected function getSitesJson($sshFull, $remoteUser) {
    $this->say('Getting ACSF sites.json information...');
    // Rsync the file.

    $destination_dir = sys_get_temp_dir() . '/acquia';
    if (!file_exists($destination_dir)) {
      mkdir($destination_dir);
    }

    $result = $this->localMachineHelper()->execute([
      'rsync',
      "$sshFull:/mnt/files/$remoteUser/files-private/sites.json",
      "$destination_dir/sites.json",
    ], NULL, $this->dir, FALSE);
    if (!$result->isSuccessful()) {
      throw new CommandFailedException($result->getErrorOutput(), $result->getExitCode());
    }

    $fullPath = $destination_dir . '/sites.json';
    $response_body = file_get_contents($fullPath);
    $sites_json = json_decode($response_body, TRUE);

    return $sites_json;
  }

  /**
   * Writes site aliases to disk.
   *
   * @param string|int $site_id
   *   The siteID or alias group.
   * @param array $aliases
   *   The alias array for this site group.
   *
   * @return string
   *   The alias site group file path.
   *
   * @throws \Exception
   */
  protected function writeSiteAliases(string|int $site_id, array $aliases) {
    $file_system = $this->localmachineHelper()->getFilesystem();
    if (!is_dir($this->aliasDir)) {
      $file_system->mkdir($this->aliasDir);
    }
    $filePath = $this->aliasDir . '/' . $site_id . '.site.yml';

    file_put_contents($filePath, Yaml::dump($aliases, 99, 2));
    return $filePath;
  }

}
