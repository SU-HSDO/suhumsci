<?php

namespace Drupal\hs_migrate\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HsMigrateList extends ControllerBase {

  /**
   * @var \Drupal\migrate\Plugin\Migration[]
   */
  protected $migrations;

  /**
   * @var mixed
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $lastMigrations;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->dateFormatter = \Drupal::service('date.formatter');
    $this->lastMigrations = \Drupal::keyValue('migrate_last_imported');

    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
    $matched_migrations = $this->migrationPluginManager->createInstances([]);
    $this->migrations = $matched_migrations;

    // Some migrations will be run when its dependent migration is ran.
    foreach ($this->migrations as $id => $migration) {
      foreach ($migration->getMigrationDependencies()['required'] as $dependency) {
        unset($this->migrations[$dependency]);
      }
    }

  }

  public function listMigrations() {
    $list = ['#type' => 'table', '#header' => $this->buildHeader()];
    foreach ($this->migrations as $migration_id => $migration) {
      $list['#rows'][$migration_id] = $this->buildRow($migration);
    }
    return $list;
  }

  /**
   * Build the table header labels.
   *
   * @return array
   *   Array of table headers.
   */
  protected function buildHeader() {
    return [
      $this->t('Importer'),
      $this->t('Status'),
      $this->t('Imported Items'),
      $this->t('Last Imported'),
      $this->t('Import'),
    ];
  }

  protected function buildRow(MigrationInterface $migration) {
    $row['label'] = [
      'data' => [
        '#markup' => $migration->label(),
      ],
    ];
    $row['status'] = $migration->getStatusLabel();

    try {
      $map = $migration->getIdMap();
      $row['imported'] = $map->importedCount();
    }
    catch (\Exception $e) {
      $row['imported'] = '0';
    }

    if ($last_imported = $this->lastMigrations->get($migration->id(), FALSE)) {
      $row['last_imported'] = $this->dateFormatter->format($last_imported / 1000, 'custom', 'M j Y g:i a');
    }
    else {
      $row['last_imported'] = '';
    }

    $row['operations']['data'] = [
      '#type' => 'dropbutton',
      '#links' => [
        'import' => [
          'title' => $this->t('Import'),
          'url' => Url::fromRoute('hs_migrate.import', [
            'migration' => $migration->id(),
          ]),
        ],
      ],
    ];

    return $row;
  }

  public function import(Migration $migration) {
    $migrateMessage = new MigrateMessage();

    $this->migrations[$migration->id()]->interruptMigration(MigrationInterface::RESULT_STOPPED);
    $this->migrations[$migration->id()]->setStatus(MigrationInterface::STATUS_IDLE);

    // Create the batch operations for each migration that needs to be executed.
    // This includes the migration for this executable, but also the dependent
    // migrations.
    $operations = $this->batchOperations([$this->migrations[$migration->id()]], 'import', [
      'limit' => 0,
      'update' => 1,
      'force' => 0,
    ]);

    if (count($operations) > 0) {
      $batch = [
        'operations' => $operations,
        'title' => t('Migrating %migrate', ['%migrate' => $migration->label()]),
        'init_message' => t('Start migrating %migrate', ['%migrate' => $migration->label()]),
        'progress_message' => t('Migrating %migrate', ['%migrate' => $migration->label()]),
        'error_message' => t('An error occurred while migrating %migrate.', ['%migrate' => $migration->label()]),
        'finished' => '\Drupal\migrate_tools\MigrateBatchExecutable::batchFinishedImport',
      ];

      batch_set($batch);
      batch_process('/import');
    }

    return [];
  }

  /**
   * Helper to generate the batch operations for importing migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations.
   * @param string $operation
   *   The batch operation to perform.
   * @param array $options
   *   The migration options.
   *
   * @return array
   *   The batch operations to perform.
   */
  protected function batchOperations(array $migrations, $operation, array $options = []) {
    $operations = [];
    foreach ($migrations as $id => $migration) {

      if (!empty($options['update'])) {
        $migration->getIdMap()->prepareUpdate();
      }

      if (!empty($options['force'])) {
        $migration->set('requirements', []);
      }
      else {
        $dependencies = $migration->getMigrationDependencies();
        if (!empty($dependencies['required'])) {
          $required_migrations = $this->migrationPluginManager->createInstances($dependencies['required']);
          // For dependent migrations will need to be migrate all items.
          $dependent_options = $options;
          $dependent_options['limit'] = 0;
          $operations += $this->batchOperations($required_migrations, $operation, [
            'limit' => 0,
            'update' => $options['update'],
            'force' => $options['force'],
          ]);
        }
      }

      $operations[] = [
        '\Drupal\migrate_tools\MigrateBatchExecutable::batchProcessImport',
        [$migration->id(), $options],
      ];
    }

    return $operations;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(AccountInterface $account) {
    if ($account->id() == 1) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
