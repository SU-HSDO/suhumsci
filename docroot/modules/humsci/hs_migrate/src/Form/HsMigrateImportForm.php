<?php

namespace Drupal\hs_migrate\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\hs_migrate\HsMigrateBatchExecutable;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Class HsMigrateImportForm.
 *
 * @package Drupal\hs_migrate\Form
 */
class HsMigrateImportForm extends FormBase {

  /**
   * Array of migration plugin objects.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected $migrations;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Key Value collection of last migrations.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $lastMigrations;

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('date.formatter'),
      $container->get('keyvalue'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationPluginManagerInterface $migrations_manager, DateFormatterInterface $date_formatter, KeyValueFactoryInterface $key_value, AccountProxyInterface $account) {
    $this->dateFormatter = $date_formatter;
    $this->lastMigrations = $key_value->get('migrate_last_imported');
    $this->migrationManager = $migrations_manager;
    $this->account = $account;

    $migrations = $this->migrationManager->createInstances([]);
    $this->migrations = $migrations;

    // No need to show migrations that are dependencies. They will get executed
    // when their dependent migration is executed.
    foreach ($migrations as $migration) {
      foreach ($migration->getMigrationDependencies()['required'] as $dependency) {
        unset($this->migrations[$dependency]);
      }
    }

    // Remove migrations that the user doesn't have access to.
    foreach (array_keys($this->migrations) as $migration_id) {
      if (!$this->account->hasPermission("import $migration_id migration")) {
        unset($this->migrations[$migration_id]);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_migrate_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('No migrations found'),
    ];
    foreach ($this->migrations as $migration_id => $migration) {
      $form['table'][$migration_id] = $this->buildRow($migration);
    }

    return $form;
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

  /**
   * Build the form row for the given migration object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration plugin object.
   *
   * @return array
   *   Form render array.
   */
  protected function buildRow(MigrationInterface $migration) {
    $row['label']['#markup'] = $migration->label();
    $row['status']['#markup'] = $migration->getStatusLabel();
    $row['imported']['#markup'] = $migration->getIdMap()->importedCount();

    if ($last_imported = $this->lastMigrations->get($migration->id(), FALSE)) {
      $row['last_imported']['#markup'] = $this->dateFormatter->format($last_imported / 1000, 'custom', 'M j Y g:i a');
    }
    else {
      $row['last_imported']['#markup'] = $this->t('Unknown');
    }

    $row['operations']['data'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#name' => $migration->id(),
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = $form_state->getTriggeringElement()['#name'];
    $migration = $this->migrations[$migration_id];

    $migration->interruptMigration(MigrationInterface::RESULT_STOPPED);
    $migration->setStatus(MigrationInterface::STATUS_IDLE);

    $migrateMessage = new MigrateMessage();
    $options = [
      'limit' => 0,
      'update' => 0,
      'force' => 0,
    ];

    $executable = new HsMigrateBatchExecutable($migration, $migrateMessage, $options);
    $executable->batchImport();
  }

  /**
   * Check if the current user has permission to any migration objects.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access result.
   */
  public function access(AccountInterface $account) {
    foreach (array_keys($this->migrations) as $migration_id) {
      if ($account->hasPermission("import $migration_id migration")) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
