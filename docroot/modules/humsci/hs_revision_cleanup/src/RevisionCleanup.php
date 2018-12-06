<?php

namespace Drupal\hs_revision_cleanup;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service to delete old revisions on entity types.
 *
 * @package Drupal\hs_revision_cleanup
 */
class RevisionCleanup {

  use StringTranslationTrait;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Config entity with cleanup settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * RevisionCleanup constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('hs_revision_cleanup');
    $this->config = $config_factory->get('hs_revision_cleanup.settings');
  }

  /**
   * Delete all revisions for the configured entity types.
   */
  public function deleteRevisions() {
    foreach ($this->config->get('cleanup') as $cleanup_entity) {
      try {
        // Subtract 1 from keep config to account for the current revision
        // because we filter out the current revision on the possible revisions
        // query.
        $this->deleteEntityRevisions($cleanup_entity['entity_type'], $cleanup_entity['keep'] - 1);
      }
      catch (\Exception $e) {
        $this->logger->error('Unable to delete entity revisions for @entity_type: @e', [
          '@entity_type' => $cleanup_entity['entity_type'],
          '@e' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Delete all old revisions of the given entity type.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param int $keep
   *   How many revisions to keep.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function deleteEntityRevisions($entity_type, $keep) {
    $revision_ids = $this->getPossibleRevisionsIds($entity_type);

    // Loop trough all entity ids and delete it's applicable keys.
    foreach ($revision_ids as $entity_id => &$revisions) {
      // Trim off the first $keep items to leave a set of revisions on each
      // entity.
      $revisions = array_slice($revisions, 0, -$keep, TRUE);

      // This entity has less than $keep number of revisions..
      if (!$revisions) {
        continue;
      }
      $this->logger->info('Deleting @count revisions from @entity_type @id', [
        '@count' => count($revisions),
        '@entity_type' => $entity_type,
        '@id' => $entity_id,
      ]);

      foreach (array_keys($revisions) as $revision_id) {
        try {
          $this->entityTypeManager->getStorage($entity_type)
            ->deleteRevision($revision_id);
        }
        catch (\Exception $e) {
          $this->logger->error('Unable to delete revision @rid. Error: @message', [
            '@rid' => $revision_id,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
  }

  /**
   * Get all revision ids for the entity type that are not CURRENT revisions.
   *
   * @param string $entity_type
   *   Entity type id.
   *
   * @return array
   *   Keyed array of entity ids and its revision ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getPossibleRevisionsIds($entity_type) {
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);

    $base_table = $entity_definition->getBaseTable();
    $revision_table = $entity_definition->getRevisionTable();

    // Safety catch if the entity type does not have a revisions.
    if (!$entity_definition->isRevisionable()) {
      return [];
    }

    $id_key = $entity_definition->getKey('id');
    $revision_key = $entity_definition->getKey('revision');

    // Query on the revision table for the entity type.
    $query = $this->database->select($revision_table, 'r')->fields('r');

    // Join the base table so that we can exclude the current revision.
    $query->join($base_table, 'b', "b.$id_key = r.$id_key");
    $query->orderBy($entity_definition->getKey('id'), 'ASC');
    $query->orderBy($revision_key, 'DESC');

    // Exclude the current revision of the entity.
    $query->where("r.$revision_key != b.$revision_key");

    $result = $query->execute();
    $revision_ids = [];
    while ($item = $result->fetchAssoc()) {
      $revision_ids[$item[$id_key]][$item[$revision_key]] = $item;
    }

    return $revision_ids;
  }

}
