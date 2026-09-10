<?php

namespace Drupal\hs_algolia;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper as ContribSearchApiAlgoliaHelper;

/**
 * Deletes Algolia records immediately instead of queueing them.
 *
 * The contrib helper only records the deletion in the
 * search_api_algolia_deleted_items table. Clearing that queue requires a
 * separate scheduled task running the module's drush command, which this
 * platform does not run. Records for deleted or unpublished content would
 * therefore stay in Algolia indefinitely. Deleting during shutdown keeps the
 * request fast while guaranteeing the record is gone.
 *
 * @see \Drupal\search_api_algolia\SearchApiAlgoliaHelper::scheduleForDeletion()
 */
class SearchApiAlgoliaHelper extends ContribSearchApiAlgoliaHelper {

  /**
   * {@inheritdoc}
   */
  public function entityDelete(EntityInterface $entity) {
    // Search API lets other code opt an entity out of indexing by setting this
    // dynamic property.
    if (!$entity instanceof ContentEntityInterface || !empty($entity->search_api_skip_tracking)) {
      return;
    }

    foreach (ContentEntity::getIndexesForEntity($entity) as $index) {
      $index_name = $index->getOption('algolia_index_name');
      $object_id_field = $index->getOption('object_id_field');

      // Indexes belonging to another backend have no Algolia index name.
      if (!$index_name || !$object_id_field || !$entity->hasField($object_id_field)) {
        continue;
      }

      $object_id = $entity->get($object_id_field)->getString();
      if (!$object_id) {
        continue;
      }

      $client = $this->getSearchClient($index);
      if (!$client) {
        continue;
      }

      drupal_register_shutdown_function(
        [self::class, 'deleteRecord'],
        $client,
        $index_name,
        $object_id
      );
    }
  }

  /**
   * Build the Algolia client for an index's server.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index whose server holds the Algolia credentials.
   *
   * @return \Algolia\AlgoliaSearch\Api\SearchClient|null
   *   The client, or NULL when the server has not been given credentials.
   */
  protected function getSearchClient(IndexInterface $index): ?SearchClient {
    $backend_config = $index->getServerInstance()?->getBackendConfig() ?? [];
    $app_id = $backend_config['application_id'] ?? '';
    $api_key = $backend_config['api_key'] ?? '';

    if (!$app_id || !$api_key) {
      return NULL;
    }

    return $this->buildAlgoliaSearchClient($app_id, $api_key);
  }

  /**
   * Delete a single record from an Algolia index.
   *
   * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
   *   Configured Algolia client.
   * @param string $index_name
   *   Algolia index name.
   * @param string $object_id
   *   Unique record identifier.
   */
  public static function deleteRecord(SearchClient $client, string $index_name, string $object_id): void {
    try {
      $client->deleteObject($index_name, $object_id);
    }
    catch (\Throwable $e) {
      // A failed deletion must never break the request that triggered it. The
      // record stays in Algolia until the next full reindex.
      \Drupal::logger('hs_algolia')->error('Unable to delete Algolia record @id from index @index: @message', [
        '@id' => $object_id,
        '@index' => $index_name,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
