<?php

namespace Drupal\hs_migrate;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;

/**
 * Class HsMigrateBatchExecutable that changes the batch methods.
 *
 * The primary object of this class is entirely to change the batch_limit in an
 * effort to import 15 items on each batch execution.
 *
 * @package Drupal\hs_migrate
 */
class HsMigrateBatchExecutable extends MigrateBatchExecutable {

  /**
   * {@inheritdoc}
   */
  public function batchImport() {
    // Create the batch operations for each migration that needs to be executed.
    // This includes the migration for this executable, but also the dependent
    // migrations.
    $operations = $this->batchOperations([$this->migration], 'import', [
      'limit' => $this->itemLimit,
      'update' => $this->updateExistingRows,
      'force' => $this->checkDependencies,
    ]);

    if (count($operations) > 0) {
      $batch = [
        'operations' => $operations,
        'title' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'init_message' => t('Start migrating %migrate', ['%migrate' => $this->migration->label()]),
        'progress_message' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'error_message' => t('An error occurred while migrating %migrate.', ['%migrate' => $this->migration->label()]),
        'finished' => '\Drupal\hs_migrate\HsMigrateBatchExecutable::batchFinishedImport',
      ];

      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function batchOperations(array $migrations, $operation, array $options = []) {
    array_walk($migrations, [$this, 'prepareMigrations']);
    $operations = parent::batchOperations($migrations, $operation, $options);
    foreach ($operations as &$operation) {
      // Change the operation to use this class instead of the parent.
      $operation[0] = [self::class, 'batchProcessImport'];
    }
    return $operations;
  }

  /**
   * Reset status of the migration and its dependency migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration object to reset.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function prepareMigrations(MigrationInterface $migration) {
    $migration->interruptMigration(MigrationInterface::RESULT_STOPPED);
    $migration->setStatus(MigrationInterface::STATUS_IDLE);

    foreach ($migration->getMigrationDependencies()['required'] as $dependency_id) {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $dependent_migration */
      $dependent_migration = $this->migrationPluginManager->createInstance($dependency_id);
      $this->prepareMigrations($dependent_migration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function batchProcessImport($migration_id, array $options, array &$context) {
    if (empty($context['sandbox'])) {
      $context['finished'] = 0;
      $context['sandbox'] = [];
      $context['sandbox']['total'] = 0;
      $context['sandbox']['counter'] = 0;
      $context['sandbox']['batch_limit'] = 0;
      $context['sandbox']['operation'] = HsMigrateBatchExecutable::BATCH_IMPORT;
    }

    // Prepare the migration executable.
    $message = new MigrateMessage();
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = \Drupal::getContainer()
      ->get('plugin.manager.migration')
      ->createInstance($migration_id);
    $executable = new HsMigrateBatchExecutable($migration, $message, $options);

    if (empty($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $executable->getSource()->count();

      // THIS is the only change from the parent class. Allow 15 items to be
      // imported on each batch execution. The parent split the total items
      // into 100 executions which doesn't really do anything helpful.
      $context['sandbox']['batch_limit'] = \Drupal::config('hs_migrate.settings')
        ->get('batch_limit') ?: 15;
      $context['results'][$migration->id()] = [
        '@numitems' => 0,
        '@created' => 0,
        '@updated' => 0,
        '@failures' => 0,
        '@ignored' => 0,
        '@name' => $migration->label(),
      ];
    }

    // Every iteration, we reset out batch counter.
    $context['sandbox']['batch_counter'] = 0;

    // Make sure we know our batch context.
    $executable->setBatchContext($context);

    // Do the import.
    $result = $executable->import();

    // Store the result; will need to combine the results of all our iterations.
    $context['results'][$migration->id()] = [
      '@numitems' => $context['results'][$migration->id()]['@numitems'] + $executable->getProcessedCount(),
      '@created' => $context['results'][$migration->id()]['@created'] + $executable->getCreatedCount(),
      '@updated' => $context['results'][$migration->id()]['@updated'] + $executable->getUpdatedCount(),
      '@failures' => $context['results'][$migration->id()]['@failures'] + $executable->getFailedCount(),
      '@ignored' => $context['results'][$migration->id()]['@ignored'] + $executable->getIgnoredCount(),
      '@name' => $migration->label(),
    ];

    // Do some housekeeping.
    if ($result != MigrationInterface::RESULT_INCOMPLETE) {
      $context['finished'] = 1;
    }
    else {
      $context['sandbox']['counter'] = $context['results'][$migration->id()]['@numitems'];
      if ($context['sandbox']['counter'] <= $context['sandbox']['total']) {
        $context['finished'] = ((float) $context['sandbox']['counter'] / (float) $context['sandbox']['total']);
        $context['message'] = t('Importing %migration (@percent%).', [
          '%migration' => $migration->label(),
          '@percent' => (int) ($context['finished'] * 100),
        ]);
      }
    }

  }

}
