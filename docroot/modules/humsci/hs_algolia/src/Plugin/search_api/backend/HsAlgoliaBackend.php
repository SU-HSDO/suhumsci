<?php

namespace Drupal\hs_algolia\Plugin\search_api\backend;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Algolia backend that never empties the shared Training Hub index.
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

    $site_id = self::siteId($this->sitePath);
    $client = $this->getAlgolia();

    foreach ($this->getLanguages($index) as $key) {
      $algolia_index_name = $this->buildAlgoliaIndexName($index, '', $key);

      // Algolia only filters on attributes declared for faceting, and a
      // filter on an undeclared one matches nothing. Fail rather than leave
      // this site's records behind, and never fall back to emptying the index.
      $facets = $client->getSettings($algolia_index_name)['attributesForFaceting'] ?? [];
      if (!preg_grep('/^(?:\w+\()?site_id\)?$/', $facets)) {
        throw new SearchApiException("Algolia index $algolia_index_name cannot filter on site_id, so this site's records were not removed. Run drush humsci:algolia:configure-federated, then retry.");
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
