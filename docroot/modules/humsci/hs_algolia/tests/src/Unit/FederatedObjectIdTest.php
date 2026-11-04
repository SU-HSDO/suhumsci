<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\hs_algolia\EventSubscriber\AnonymousAccessSubscriber;
use Drupal\hs_algolia\HsAlgoliaHelper;
use Drupal\hs_algolia\HsAlgoliaServiceProvider;
use Drupal\hs_algolia\Plugin\search_api\backend\HsAlgoliaBackend;
use Drupal\node\Entity\Node;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntityTrackingManager;
use Drupal\search_api\ServerInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests that Training Hub records are keyed on the site as well as the UUID.
 *
 * UUIDs are not unique across sites. Sites cloned from a template, and
 * content copied with single_content_sync, share them. Every path that sends
 * or deletes a record must use the same site-scoped objectID, or one site
 * overwrites another's training and deletions silently miss.
 */
#[Group('hs_algolia')]
class FederatedObjectIdTest extends UnitTestCase {

  /**
   * Shared Algolia index name used by the federated index.
   */
  const FEDERATED_INDEX_NAME = 'hsdp_trainings';

  /**
   * Per-site Algolia index name used by the site index.
   */
  const SITE_INDEX_NAME = 'archaeology';

  /**
   * UUID the test node carries on every site.
   */
  const UUID = '5f0c6d2e-3b1a-4c55-9d8e-2a7b6c1d0e9f';

  /**
   * Rows in the search_api_algolia_deleted_items table.
   *
   * @var array
   */
  protected array $queue = [];

  /**
   * Object IDs Algolia was asked to delete, keyed by Algolia index name.
   *
   * @var array
   */
  protected array $deleted = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
    $this->queue = [];
    $this->deleted = [];
  }

  /**
   * Build an index on an Algolia server keyed on UUID.
   *
   * @param string $id
   *   Search API index ID.
   * @param string $index_name
   *   Algolia index name.
   */
  protected function mockIndex(string $id, string $index_name): IndexInterface {
    $client = $this->createMock(SearchClient::class);
    $client->method('deleteObjects')->willReturnCallback(
      function (string $indexName, array $objectIDs) {
        $this->deleted[$indexName] = array_merge($this->deleted[$indexName] ?? [], $objectIDs);
        return [];
      }
    );
    $backend = $this->createMock(SearchApiAlgoliaBackend::class);
    $backend->method('getAlgolia')->willReturn($client);
    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackend')->willReturn($backend);

    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn($id);
    $index->method('get')->willReturn(NULL);
    $index->method('getOption')->willReturnCallback(fn($name) => match ($name) {
      'algolia_index_name' => $index_name,
      'object_id_field' => 'uuid',
      default => NULL,
    });
    $index->method('status')->willReturn(TRUE);
    $index->method('isReadOnly')->willReturn(FALSE);
    $index->method('isServerEnabled')->willReturn(TRUE);
    $index->method('hasValidServer')->willReturn(TRUE);
    $index->method('getServerInstance')->willReturn($server);
    $index->method('isValidProcessor')->willReturn(FALSE);
    return $index;
  }

  /**
   * Build a node carrying the shared UUID.
   *
   * @param bool $published
   *   Whether the node is published now.
   * @param bool $was_published
   *   Whether it was published before this save.
   */
  protected function mockNode(bool $published = TRUE, bool $was_published = TRUE): Node {
    $uuid = $this->createMock(FieldItemListInterface::class);
    $uuid->method('getString')->willReturn(self::UUID);

    $original = $this->createMock(Node::class);
    $original->method('isPublished')->willReturn($was_published);

    $node = $this->createMock(Node::class);
    $node->method('hasField')->willReturn(TRUE);
    $node->method('get')->willReturn($uuid);
    $node->method('isPublished')->willReturn($published);
    $node->method('getOriginal')->willReturn($original);
    $node->method('access')->willReturn(FALSE);
    return $node;
  }

  /**
   * Build a database whose deletion queue lives in $this->queue.
   */
  protected function mockDatabase(): Connection {
    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->method('values')->willReturnCallback(function (array $values) use ($insert) {
      $this->queue[] = $values;
      return $insert;
    });

    $connection = $this->createMock(Connection::class);
    $connection->method('insert')->willReturn($insert);
    $connection->method('select')->willReturnCallback(function () {
      $statement = $this->createMock(StatementInterface::class);
      $statement->method('fetchAll')->willReturn(array_map(fn($row) => (object) $row, $this->queue));
      $select = $this->createMock(Select::class);
      $select->method('fields')->willReturnSelf();
      $select->method('execute')->willReturn($statement);
      return $select;
    });
    $connection->method('delete')->willReturnCallback(function () {
      $conditions = [];
      $delete = $this->createMock(Delete::class);
      $delete->method('condition')->willReturnCallback(
        function ($field, $value) use (&$conditions, $delete) {
          $conditions[$field] = (array) $value;
          return $delete;
        }
      );
      $delete->method('execute')->willReturnCallback(function () use (&$conditions) {
        $this->queue = array_values(array_filter($this->queue, fn($row) => !(
          in_array($row['index_id'], $conditions['index_id'], TRUE)
          && in_array($row['object_id'], $conditions['object_id'], TRUE)
        )));
        return 1;
      });
      return $delete;
    });
    return $connection;
  }

  /**
   * Register the real helper for a site holding both indexes.
   *
   * @param string $site_path
   *   Drupal site directory.
   * @param string $federated_index_name
   *   Algolia index name of the shared index, empty where none is set.
   *
   * @return \Drupal\hs_algolia\HsAlgoliaHelper
   *   The helper every delete path reaches through the container.
   */
  protected function setUpSite(string $site_path = 'sites/hs_sandbox', string $federated_index_name = self::FEDERATED_INDEX_NAME): HsAlgoliaHelper {
    $indexes = [
      $this->mockIndex('hs_algolia', self::SITE_INDEX_NAME),
      $this->mockIndex(HS_ALGOLIA_FEDERATED_INDEX_ID, $federated_index_name),
    ];

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($indexes);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);
    $repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $repository->method('getEntityTypeFromClass')->willReturn('search_api_index');

    $tracking_manager = $this->createMock(ContentEntityTrackingManager::class);
    $tracking_manager->method('getIndexesForEntity')->willReturn($indexes);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->createMock(LoggerInterface::class));

    $database = $this->mockDatabase();
    $helper = new HsAlgoliaHelper(
      $entity_type_manager,
      $this->createMock(LanguageManagerInterface::class),
      $database,
      $this->createMock(ModuleHandlerInterface::class),
    );
    $helper->setSitePath($site_path);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $repository);
    $container->set('search_api.entity_datasource.tracking_manager', $tracking_manager);
    $container->set('search_api_algolia.helper', $helper);
    $container->set('database', $database);
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    return $helper;
  }

  /**
   * The object IDs queued for deletion, keyed by Algolia index name.
   */
  protected function queuedIds(): array {
    $queued = [];
    foreach ($this->queue as $row) {
      $queued[$row['index_id']][] = $row['object_id'];
    }
    return $queued;
  }

  /**
   * Build a backend for one site, without its plugin constructor.
   */
  protected function buildBackend(string $site_path): HsAlgoliaBackend {
    $fields_helper = $this->createMock(FieldsHelperInterface::class);
    $fields_helper->method('createField')->willReturnCallback(
      fn(IndexInterface $index, string $id) => new Field($index, $id)
    );

    $backend = (new \ReflectionClass(HsAlgoliaBackend::class))->newInstanceWithoutConstructor();
    foreach ([
      'configFactory' => $this->createMock(ConfigFactoryInterface::class),
      'fieldsHelper' => $fields_helper,
      'sitePath' => $site_path,
    ] as $property => $value) {
      (new \ReflectionProperty($backend, $property))->setValue($backend, $value);
    }
    return $backend;
  }

  /**
   * Prepare the record a site would send for the shared node.
   */
  protected function prepareRecord(string $site_path, IndexInterface $index): array {
    $entity = $this->createMock(ComplexDataInterface::class);
    $entity->method('getValue')->willReturn($this->mockNode());
    $item = $this->createMock(ItemInterface::class);
    $item->method('getId')->willReturn('entity:node/1:en');
    $item->method('getOriginalObject')->willReturn($entity);
    $item->method('getFields')->willReturn([]);

    $method = new \ReflectionMethod(HsAlgoliaBackend::class, 'prepareItem');
    return $method->invoke($this->buildBackend($site_path), $index, $item);
  }

  /**
   * Two sites holding the same UUID send two distinct records.
   */
  public function testSameUuidOnTwoSitesGetsDistinctObjectIds(): void {
    $index = $this->mockIndex(HS_ALGOLIA_FEDERATED_INDEX_ID, self::FEDERATED_INDEX_NAME);

    $sandbox = $this->prepareRecord('sites/hs_sandbox', $index);
    $sandbox_1 = $this->prepareRecord('sites/hs_sandbox_1', $index);

    $this->assertSame('hs_sandbox:' . self::UUID, $sandbox['objectID']);
    $this->assertSame('hs_sandbox_1:' . self::UUID, $sandbox_1['objectID']);
  }

  /**
   * A site's own index is still keyed on the bare UUID.
   */
  public function testPerSiteIndexKeepsBareUuid(): void {
    $record = $this->prepareRecord('sites/hs_sandbox', $this->mockIndex('hs_algolia', self::SITE_INDEX_NAME));

    $this->assertSame(self::UUID, $record['objectID']);
  }

  /**
   * An ID that already carries this site's prefix is not prefixed again.
   */
  public function testFederatedObjectIdIsNotPrefixedTwice(): void {
    $object_id = HsAlgoliaBackend::federatedObjectId('hs_sandbox', self::UUID);

    $this->assertSame($object_id, HsAlgoliaBackend::federatedObjectId('hs_sandbox', $object_id));
    // A site whose name extends another's still gets its own prefix.
    $this->assertSame('hs_sandbox_1:' . $object_id, HsAlgoliaBackend::federatedObjectId('hs_sandbox_1', $object_id));
  }

  /**
   * Every path queues the site-scoped ID for the shared index only.
   */
  public function testEveryDeletePathQueuesSiteScopedId(): void {
    $paths = [
      'entity delete' => fn(Node $node) => hs_algolia_entity_delete($node),
      'unpublish' => fn(Node $node) => hs_algolia_node_update($node),
      'content_access restriction' => fn(Node $node) => hs_algolia_content_access_per_node(['view' => []], $node),
      'anonymous access check' => fn(Node $node) => $this->runAnonymousAccessCheck($node),
    ];

    foreach ($paths as $name => $path) {
      $this->queue = [];
      $this->setUpSite('sites/hs_sandbox_1');

      $path($this->mockNode(FALSE, TRUE));

      $this->assertSame([
        self::SITE_INDEX_NAME => [self::UUID],
        self::FEDERATED_INDEX_NAME => ['hs_sandbox_1:' . self::UUID],
      ], $this->queuedIds(), "$name queues the wrong object IDs.");
    }
  }

  /**
   * Run the anonymous access check on an index the node is being sent to.
   */
  protected function runAnonymousAccessCheck(Node $node): void {
    $object = $this->createMock(ComplexDataInterface::class);
    $object->method('getValue')->willReturn($node);
    $item = $this->createMock(ItemInterface::class);
    $item->method('getOriginalObject')->willReturn($object);

    $index = $this->mockIndex(HS_ALGOLIA_FEDERATED_INDEX_ID, self::FEDERATED_INDEX_NAME);
    $subscriber = new AnonymousAccessSubscriber(\Drupal::service('search_api_algolia.helper'));
    $subscriber->filterAnonymousAccess(new IndexingItemsEvent($index, ['entity:node/1:en' => $item]));
  }

  /**
   * Cron deletes exactly the IDs that were queued, from the right index.
   */
  public function testCronDeletesSiteScopedId(): void {
    $this->setUpSite();
    hs_algolia_entity_delete($this->mockNode());

    hs_algolia_cron();

    $this->assertSame([
      self::SITE_INDEX_NAME => [self::UUID],
      self::FEDERATED_INDEX_NAME => ['hs_sandbox:' . self::UUID],
    ], $this->deleted);
    $this->assertSame([], $this->queue);
  }

  /**
   * A site with the shared index enabled but no index name queues nothing.
   *
   * Stage and dev get no Training Hub settings. A deletion row with an empty
   * index name could never be drained, and cron would log it every run.
   */
  public function testUnnamedFederatedIndexQueuesNothing(): void {
    $this->setUpSite('sites/hs_sandbox', '');

    hs_algolia_entity_delete($this->mockNode());
    $this->assertSame([self::SITE_INDEX_NAME => [self::UUID]], $this->queuedIds());
    $this->assertArrayNotHasKey('', hs_algolia_active_indexes());

    hs_algolia_cron();
    $this->assertSame([self::SITE_INDEX_NAME => [self::UUID]], $this->deleted);
    $this->assertSame([], $this->queue);
  }

  /**
   * Reindexing a node cancels its queued deletion under the same ID.
   */
  public function testReindexCancelsSiteScopedDeletion(): void {
    $this->setUpSite();
    hs_algolia_entity_delete($this->mockNode());

    $index = $this->mockIndex(HS_ALGOLIA_FEDERATED_INDEX_ID, self::FEDERATED_INDEX_NAME);
    hs_algolia_cancel_queued_deletions([
      'entity:node/1:en' => ['objectID' => 'hs_sandbox:' . self::UUID],
    ], $index);

    $this->assertSame([self::SITE_INDEX_NAME => [self::UUID]], $this->queuedIds());
  }

  /**
   * The contrib helper service is replaced, with this site's directory.
   */
  public function testHelperServiceIsReplaced(): void {
    $container = new ContainerBuilder();
    $container->setDefinition('search_api_algolia.helper', new Definition(SearchApiAlgoliaHelper::class));

    (new HsAlgoliaServiceProvider())->alter($container);

    $definition = $container->getDefinition('search_api_algolia.helper');
    $this->assertSame(HsAlgoliaHelper::class, $definition->getClass());
    $this->assertSame([['setSitePath', ['%site.path%']]], $definition->getMethodCalls());
  }

}
