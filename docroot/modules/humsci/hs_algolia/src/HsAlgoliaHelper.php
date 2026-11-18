<?php

namespace Drupal\hs_algolia;

use Drupal\hs_algolia\Plugin\search_api\backend\HsAlgoliaBackend;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;

/**
 * Queues Training Hub deletions under the same objectID they were sent with.
 *
 * Every queued deletion passes through scheduleForDeletion(): entity delete,
 * unpublish, content_access restriction and the anonymous access check. The
 * contrib helper queues the bare UUID, which in the shared index would match
 * nothing, or another site's record.
 *
 * @see \Drupal\hs_algolia\HsAlgoliaServiceProvider
 * @see \Drupal\hs_algolia\Plugin\search_api\backend\HsAlgoliaBackend::prepareItem()
 */
class HsAlgoliaHelper extends SearchApiAlgoliaHelper {

  /**
   * Drupal site directory.
   */
  protected string $sitePath = '';

  /**
   * Set the Drupal site directory.
   *
   * @param string $site_path
   *   Drupal site directory.
   */
  public function setSitePath(string $site_path): void {
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleForDeletion(IndexInterface $index, array $item_ids, $language = NULL) {
    // An index without an Algolia index name has sent nothing to delete, and
    // a row with an empty index name could never be drained.
    if (!$index->getOption('algolia_index_name')) {
      return;
    }

    if ($index->id() === HS_ALGOLIA_FEDERATED_INDEX_ID) {
      $site_id = HsAlgoliaBackend::siteId($this->sitePath);
      $item_ids = array_map(
        fn($id) => HsAlgoliaBackend::federatedObjectId($site_id, (string) $id),
        $item_ids
      );
    }

    parent::scheduleForDeletion($index, $item_ids, $language);
  }

}
