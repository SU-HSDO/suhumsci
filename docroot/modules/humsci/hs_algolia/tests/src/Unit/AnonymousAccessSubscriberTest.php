<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\hs_algolia\EventSubscriber\AnonymousAccessSubscriber;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that content hidden from anonymous users never reaches Algolia.
 */
#[Group('hs_algolia')]
class AnonymousAccessSubscriberTest extends UnitTestCase {

  /**
   * Build an index whose server uses the given backend.
   */
  protected function mockIndex(BackendInterface $backend, bool $valid_server = TRUE): IndexInterface {
    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackend')->willReturn($backend);

    $index = $this->createMock(IndexInterface::class);
    $index->method('hasValidServer')->willReturn($valid_server);
    $index->method('getServerInstance')->willReturn($server);
    return $index;
  }

  /**
   * Build an item wrapping an entity with the given anonymous view access.
   *
   * @return array{0: \Drupal\search_api\Item\ItemInterface, 1: \Drupal\Core\Entity\EntityInterface}
   *   The item and the entity it wraps.
   */
  protected function mockItem(bool $anonymous_can_view): array {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('access')
      ->with('view', $this->isInstanceOf(AccountInterface::class))
      ->willReturn($anonymous_can_view);

    $object = $this->createMock(ComplexDataInterface::class);
    $object->method('getValue')->willReturn($entity);

    $item = $this->createMock(ItemInterface::class);
    $item->method('getOriginalObject')->willReturn($object);
    return [$item, $entity];
  }

  /**
   * The subscriber listens to the indexing event.
   */
  public function testSubscribesToIndexingEvent(): void {
    $this->assertArrayHasKey(SearchApiEvents::INDEXING_ITEMS, AnonymousAccessSubscriber::getSubscribedEvents());
  }

  /**
   * Restricted items are dropped and queued for deletion; public ones stay.
   */
  public function testRestrictedItemsAreDroppedAndQueued(): void {
    [$public_item] = $this->mockItem(TRUE);
    [$restricted_item, $restricted_entity] = $this->mockItem(FALSE);

    $helper = $this->createMock(SearchApiAlgoliaHelper::class);
    $helper->expects($this->once())->method('entityDelete')->with($restricted_entity);

    $index = $this->mockIndex($this->createMock(SearchApiAlgoliaBackend::class));
    $event = new IndexingItemsEvent($index, [
      'entity:node/1:en' => $public_item,
      'entity:node/2:en' => $restricted_item,
    ]);

    (new AnonymousAccessSubscriber($helper))->filterAnonymousAccess($event);

    $this->assertSame(['entity:node/1:en'], array_keys($event->getItems()));
  }

  /**
   * Indexes on other backends are left alone.
   *
   * Private content must still reach the database index.
   */
  public function testOtherBackendsAreUntouched(): void {
    [$restricted_item] = $this->mockItem(FALSE);

    $helper = $this->createMock(SearchApiAlgoliaHelper::class);
    $helper->expects($this->never())->method('entityDelete');

    $index = $this->mockIndex($this->createMock(BackendInterface::class));
    $event = new IndexingItemsEvent($index, ['entity:node/2:en' => $restricted_item]);

    (new AnonymousAccessSubscriber($helper))->filterAnonymousAccess($event);

    $this->assertSame(['entity:node/2:en'], array_keys($event->getItems()));
  }

  /**
   * An index with no valid server is left alone rather than erroring.
   */
  public function testIndexWithoutServerIsUntouched(): void {
    [$restricted_item] = $this->mockItem(FALSE);

    $index = $this->mockIndex($this->createMock(SearchApiAlgoliaBackend::class), FALSE);
    $event = new IndexingItemsEvent($index, ['entity:node/2:en' => $restricted_item]);

    (new AnonymousAccessSubscriber($this->createMock(SearchApiAlgoliaHelper::class)))->filterAnonymousAccess($event);

    $this->assertCount(1, $event->getItems());
  }

  /**
   * Items that do not wrap an entity are passed through.
   */
  public function testNonEntityItemsPassThrough(): void {
    $object = $this->createMock(ComplexDataInterface::class);
    $object->method('getValue')->willReturn(['not' => 'an entity']);
    $item = $this->createMock(ItemInterface::class);
    $item->method('getOriginalObject')->willReturn($object);

    $index = $this->mockIndex($this->createMock(SearchApiAlgoliaBackend::class));
    $event = new IndexingItemsEvent($index, ['custom:1' => $item]);

    (new AnonymousAccessSubscriber($this->createMock(SearchApiAlgoliaHelper::class)))->filterAnonymousAccess($event);

    $this->assertCount(1, $event->getItems());
  }

}
