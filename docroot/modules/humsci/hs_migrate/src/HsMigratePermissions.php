<?php

namespace Drupal\hs_migrate;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HsMigratePermissions.
 *
 * @package Drupal\hs_migrate
 */
class HsMigratePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * Array of migrations objects.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected $migrations;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.migration'));
  }

  /**
   * HsMigratePermissions constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrations_manager
   *   Migration plugin manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(MigrationPluginManagerInterface $migrations_manager) {
    $this->migrationManager = $migrations_manager;
    $this->migrations = $this->migrationManager->createInstances([]);

    // Some migrations will be run when its dependent migration is ran.
    foreach ($this->migrations as $migration) {
      foreach ($migration->getMigrationDependencies()['required'] as $dependency) {
        unset($this->migrations[$dependency]);
      }
    }
  }

  /**
   * Build a list of permissions for the available migrations.
   *
   * @return array
   *   Keyed array of permission data.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->migrations as $migration_id => $migration) {
      $permissions["import $migration_id migration"] = [
        'title' => $this->t('Execute Migration %label', ['%label' => $migration->label()]),
        'description' => $this->t('Run importer on /import page'),
      ];
    }

    return $permissions;
  }

}
