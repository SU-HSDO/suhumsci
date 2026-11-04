<?php

namespace Drupal\hs_algolia\Plugin\search_api\backend;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Algolia backend that keeps each site's Training Hub records separate.
 *
 * Every site writes into one shared index, so records are keyed on the site
 * as well as the entity, and clearing the index never empties it.
 *
 * @see hs_algolia_search_api_backend_info_alter()
 */
class HsAlgoliaBackend extends SearchApiAlgoliaBackend {

  /**
   * Drupal site directory.
   */
  protected string $sitePath;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sitePath = $container->getParameter('site.path');
    return $instance;
  }

  /**
   * Get the stable key identifying this site's records in a shared index.
   *
   * @param string $site_path
   *   Drupal site directory.
   *
   * @return string
   *   The site directory name, which is the same in every environment.
   */
  public static function siteId(string $site_path): string {
    return basename($site_path);
  }

  /**
   * Build a record's objectID in the shared Training Hub index.
   *
   * UUIDs are not unique across sites. Sites cloned from a template, and
   * content copied with single_content_sync, share them, so a bare UUID would
   * let one site's training overwrite another's.
   *
   * @param string $site_id
   *   Site the record belongs to.
   * @param string $id
   *   The record's own ID, normally the entity UUID.
   *
   * @return string
   *   The ID as "<site_id>:<id>". An ID that already carries the prefix is
   *   returned unchanged.
   */
  public static function federatedObjectId(string $site_id, string $id): string {
    $prefix = $site_id . ':';
    return str_starts_with($id, $prefix) ? $id : $prefix . $id;
  }

  /**
   * Whether an index is the shared index without an Algolia index name.
   *
   * Only production and local development name the shared index. The
   * contrib backend falls back to the Search API index ID for an empty name,
   * so without this check an enabled index would write to an Algolia index
   * called hs_algolia_trainings.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index being written to.
   *
   * @return bool
   *   TRUE when nothing should be written for this index.
   */
  protected static function isUnnamedFederatedIndex(IndexInterface $index): bool {
    return $index->id() === HS_ALGOLIA_FEDERATED_INDEX_ID && !$index->getOption('algolia_index_name');
  }

  /**
   * {@inheritdoc}
   *
   * On an environment without a Training Hub index name, report the items as
   * processed without sending them, so cron does not reload them every run.
   */
  public function indexItems(IndexInterface $index, array $items) {
    if (self::isUnnamedFederatedIndex($index)) {
      $this->logger->notice('Skipped sending @count items to the Training Hub: this environment sets no Algolia index name for @index.', [
        '@count' => count($items),
        '@index' => $index->id(),
      ]);
      return array_keys($items);
    }

    return parent::indexItems($index, $items);
  }

  /**
   * {@inheritdoc}
   *
   * The shared index keeps object_id_field set to uuid, which makes the
   * contrib backend ignore Search API's deletion of items that fail to load.
   * Records from other sites never load here, so that is what stops a site
   * deleting them. The site prefix is added on top of the UUID.
   *
   * @see \Drupal\hs_algolia\HsAlgoliaHelper::scheduleForDeletion()
   */
  protected function prepareItem(IndexInterface $index, ItemInterface $item) {
    $object = parent::prepareItem($index, $item);

    if ($index->id() === HS_ALGOLIA_FEDERATED_INDEX_ID) {
      $object['objectID'] = self::federatedObjectId(self::siteId($this->sitePath), $object['objectID']);
    }

    return $object;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   *   When the shared index cannot yet filter on site_id. Search API logs the
   *   failure and retries the removal as a server task.
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    if ($index->id() !== HS_ALGOLIA_FEDERATED_INDEX_ID) {
      parent::deleteAllIndexItems($index, $datasource_id);
      return;
    }

    // Nothing was written without an index name, so there is nothing to clear.
    if (self::isUnnamedFederatedIndex($index)) {
      return;
    }

    $site_id = self::siteId($this->sitePath);
    $client = $this->getAlgolia();

    foreach ($this->getLanguages($index) as $key) {
      $algolia_index_name = $this->buildAlgoliaIndexName($index, '', $key);

      // Algolia only filters on attributes declared for faceting, and a
      // filter on an undeclared one matches nothing. Fail rather than leave
      // this site's records behind, and never fall back to emptying the index.
      $facets = $client->getSettings($algolia_index_name)['attributesForFaceting'] ?? [];
      if (!preg_grep('/^(?:\w+\()?site_id\)?$/', $facets)) {
        throw new SearchApiException("Algolia index $algolia_index_name cannot filter on site_id, so this site's records were not removed. Add site_id to attributesForFaceting in the Algolia dashboard, as described in docs/AlgoliaSearch.md, and the removal is retried on cron.");
      }

      // Replicas mirror the primary's records, so they follow this deletion.
      $response = $client->deleteBy($algolia_index_name, [
        'filters' => 'site_id:"' . addcslashes($site_id, '"\\') . '"',
      ]);
      $this->logger->notice('Removed records for site @site from shared Algolia index @index.', [
        '@site' => $site_id,
        '@index' => $algolia_index_name,
      ]);

      if ($this->shouldWaitForDeleteToFinish()) {
        $client->waitForTask(indexName: $algolia_index_name, taskId: $response['taskID']);
      }
    }
  }

}
