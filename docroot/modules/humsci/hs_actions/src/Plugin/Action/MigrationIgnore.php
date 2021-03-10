<?php

namespace Drupal\hs_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clones a node.
 *
 * @Action(
 *   id = "migration_ignore",
 *   label = @Translation("Ignore from importing"),
 *   type = "node"
 * )
 */
class MigrationIgnore extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->migrationPluginManager = $migration_plugin_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account->hasPermission('ignore content from importer')) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }

    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->access('create', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface) {
      foreach ($this->migrationPluginManager->getDefinitions() as $definition) {
        $table = "migrate_map_{$definition['id']}";
        if (
          $this->database->schema()->tableExists($table) &&
          $this->database->schema()->fieldExists($table, 'destid1') &&
          $this->database->schema()->fieldExists($table, 'source_row_status')
        ) {
          $this->database->update($table)
            ->fields(['source_row_status' => MigrateIdMapInterface::STATUS_IGNORED])
            ->condition('destid1', $entity->id())
            ->execute();
        }
      }
    }
  }

}
