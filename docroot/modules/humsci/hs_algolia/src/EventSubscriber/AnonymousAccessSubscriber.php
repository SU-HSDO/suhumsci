<?php

namespace Drupal\hs_algolia\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps content the anonymous user cannot view out of Algolia.
 *
 * Algolia records are queried from the browser with a public key, so every
 * record is public. Sites can restrict published content with content_access
 * or per-node grants, and the entity_status processor does not see that. This
 * runs the full node access check as the anonymous user for every item bound
 * for an Algolia index. It is an event subscriber rather than a processor so
 * it cannot be switched off through the index form.
 */
class AnonymousAccessSubscriber implements EventSubscriberInterface {

  public function __construct(protected SearchApiAlgoliaHelper $helper) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [SearchApiEvents::INDEXING_ITEMS => 'filterAnonymousAccess'];
  }

  /**
   * Drop items the anonymous user cannot view and queue their removal.
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   The indexing event.
   */
  public function filterAnonymousAccess(IndexingItemsEvent $event): void {
    if (!static::isAlgoliaIndex($event->getIndex())) {
      return;
    }

    $anonymous = new AnonymousUserSession();
    $items = $event->getItems();

    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()?->getValue();
      if (!$entity instanceof EntityInterface || $entity->access('view', $anonymous)) {
        continue;
      }

      unset($items[$item_id]);

      // Search API asks the backend to delete rejected items, but the Algolia
      // backend ignores that call when an object ID field is set. Queue the
      // record so cron removes it if it was indexed before access changed.
      $this->helper->entityDelete($entity);
    }

    $event->setItems($items);
  }

  /**
   * Whether an index sends its records to Algolia.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index being processed.
   *
   * @return bool
   *   TRUE for indexes on an Algolia server.
   */
  protected static function isAlgoliaIndex(IndexInterface $index): bool {
    return $index->hasValidServer()
      && $index->getServerInstance()->getBackend() instanceof SearchApiAlgoliaBackend;
  }

}
