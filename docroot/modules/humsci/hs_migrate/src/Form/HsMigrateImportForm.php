<?php

namespace Drupal\hs_migrate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_tools\MigrateBatchExecutable;

class HsMigrateImportForm extends FormBase {

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
    return parent::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct() {

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

  protected function buildRow(MigrationInterface $migration) {
    $row['label']['#markup'] = $migration->label();
    $row['status']['#markup'] = $migration->getStatusLabel();

    try {
      $map = $migration->getIdMap();
      $row['imported']['#markup'] = $map->importedCount();
    }
    catch (\Exception $e) {
      $row['imported']['#markup'] = '0';
    }

    if ($last_imported = $this->lastMigrations->get($migration->id(), FALSE)) {
      $row['last_imported']['#markup'] = $this->dateFormatter->format($last_imported / 1000, 'custom', 'M j Y g:i a');
    }
    else {
      $row['last_imported']['#markup'] = '';
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

    $migrateMessage = new MigrateMessage();
    $options = ['limit' => 0, 'update' => 1, 'force' => 0];

    $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
    $executable->batchImport();
  }

}