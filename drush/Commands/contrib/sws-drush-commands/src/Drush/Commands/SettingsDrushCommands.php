<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drush\Boot\DrupalBootLevels;
use Drush\Utils\StringUtils;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class SettingsDrushCommands extends DrushCommands
{

  use SwsCommandsTrait;

  /**
   * Get config value of drush.
   */
  #[CLI\Command(name: 'sws:drush-config')]
  public function getDrushConfig(string $config, array $options = [
    'format' => 'text',
  ]
  )
  {
    $value = $this->getConfig()->get($config);
    if ($options['format'] === 'json') {
      echo json_encode($value);
      return;
    }

    if (is_array($value)) {
      foreach ($value as $item) {
        echo is_array($item) || is_object($item) ? gettype($item) : $item;
        echo PHP_EOL;
      }
    }
  }

  /**
   * Generates default settings files for Drupal and drush.
   *
   * Replaces `blt settings`.
   */
  #[CLI\Command(name: 'sws:multisite:settings', aliases: ['settings'])]
  #[CLI\Option(name: 'db-port', description: 'Database Port')]
  #[CLI\Option(name: 'db-host', description: 'Database Host')]
  #[CLI\Option(name: 'db-user', description: 'Database User')]
  #[CLI\Option(name: 'db-pass', description: 'Database Password')]
  #[CLI\Option(name: 'db-name', description: 'Database Name')]
  #[CLI\Option(name: 'multisites', description: 'List of multisites tracked in the code base. This should be stored in drush/drush.yml file.')]
  #[CLI\Usage(name: 'drush settings --db-port=3306 --db-host=mysql --db-user=root --db-pass=root --db-name=foobar', description: 'Build settings files using the provided database credentials.')]
  public function buildSettings(array $options = [
    'db-port' => 3306,
    'db-host' => 'localhost',
    'db-user' => 'user',
    'db-pass' => 'password',
    'db-name' => 'drupal',
    'multisites' => ['default'],
  ]
  )
  {
    $sites_dir = $this->getDir() . '/docroot/sites';

    // Generate hash file in salt.txt.
    $this->hashSalt();

    $default_project_default_settings_file = "$sites_dir/default/default.settings.php";
    $blt_local_settings_file = __DIR__ . '/../../../settings/default.local.settings.php';
    $blt_includes_settings_file = __DIR__ . '/../../../settings/default.includes.settings.php';
    $blt_glob_settings_file = __DIR__ . '/../../../settings/default.global.settings.php';
    $blt_local_drush_file = __DIR__ . '/../../../settings/default.local.drush.yml';

    $xpand_config = [
      '${drupal.db.port}' => $options['db-port'],
      '${drupal.db.host}' => $options['db-host'],
      '${drupal.db.username}' => $options['db-user'],
      '${drupal.db.password}' => $options['db-pass'],
      '${drupal.db.database}' => $options['db-name'],
    ];
    $file_system = $this->localmachineHelper()->getFilesystem();

    $global_copy_map = [
      "/$sites_dir/settings/default.local.settings.php" => "/$sites_dir/settings/local.settings.php"
    ];

    foreach ($options['multisites'] as $multisite) {
      // Generate settings.php.
      $multisite_dir = "/$sites_dir/$multisite";
      $project_default_settings_file = "$multisite_dir/default.settings.php";
      $project_settings_file = "$multisite_dir/settings.php";

      // Generate local.settings.php.
      $default_local_settings_file = "$multisite_dir/settings/default.local.settings.php";
      $project_local_settings_file = "$multisite_dir/settings/local.settings.php";

      // Generate default.includes.settings.php.
      $default_includes_settings_file = "$multisite_dir/settings/default.includes.settings.php";

      // Generate sites/settings/default.global.settings.php.
      $default_glob_settings_file = $this->getDir() . "/docroot/sites/settings/default.global.settings.php";

      // Generate local.drush.yml.
      $default_local_drush_file = "$multisite_dir/default.local.drush.yml";
      $project_local_drush_file = "$multisite_dir/local.drush.yml";

      $copy_map = $global_copy_map + [
        $blt_local_settings_file => $default_local_settings_file,
        $default_local_settings_file => $project_local_settings_file,
        $blt_includes_settings_file => $default_includes_settings_file,
        $blt_local_drush_file => $default_local_drush_file,
        $default_local_drush_file => $project_local_drush_file,
        $blt_glob_settings_file => $default_glob_settings_file,
        $default_project_default_settings_file => $project_default_settings_file,
        $project_default_settings_file => $project_settings_file,
      ];
      // Define an array of files that require property expansion.
      $expand_map = [
        $default_local_settings_file => $project_local_settings_file,
        $default_local_drush_file => $project_local_drush_file,
      ];

      // Copy files without overwriting.
      foreach ($copy_map as $from => $to) {
        if ($file_system->exists($from) && !$file_system->exists($to)) {
          $file_system->copy($from, $to);
        }
      }

      foreach ($expand_map as $to) {
        $file_contents = file_get_contents($to);
        $file_contents = str_replace(array_keys($xpand_config), array_values($xpand_config), $file_contents);
        file_put_contents($to, $file_contents);
      }

      $project_settings_file_contents = file_get_contents($project_settings_file);
      if (!preg_grep('/^.*?sws\.settings\.php/', explode("\n", $project_settings_file_contents))) {
        $project_settings_file_contents .= PHP_EOL . "require DRUPAL_ROOT . '/../drush/Commands/contrib/sws-drush-commands/settings/sws.settings.php';" . PHP_EOL;
        file_put_contents($project_settings_file, $project_settings_file_contents);
      }
    }
  }

  /**
   * Writes a hash salt to ${repo.root}/salt.txt if one does not exist.
   */
  #[CLI\Command(name: 'sws:drupal:hash-salt:init', aliases: [
    'dhsi',
    'setup:hash-salt',
  ])]
  public function hashSalt()
  {
    $file_system = $this->localmachineHelper()->getFilesystem();
    $hash_salt_file = $this->getDir() . '/salt.txt';

    if (!$file_system->exists($hash_salt_file)) {
      $this->say("Generating hash salt...");
      file_put_contents($hash_salt_file, StringUtils::generatePassword(55));
    }
  }

}
