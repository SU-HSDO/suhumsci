<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntityTrackingManager;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that a content_access change re-evaluates Algolia records.
 *
 * Restricting content through content_access writes node grants without
 * saving the node, so nothing re-tracks the item and an already indexed
 * record stays in a browser-queryable index.
 */
#[Group('hs_algolia')]
class ContentAccessRetrackTest extends UnitTestCase {

  /**
   * Item IDs each index was asked to re-track, keyed by index ID.
   *
   * @var array
   */
  protected array $tracked = [];

  /**
   * Nodes queued for removal from Algolia.
   *
   * @var array
   */
  protected array $queued = [];

  /**
   * Nodes handed back to Search API's tracker.
   *
   * @var array
   */
  protected array $retracked = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
    $this->tracked = [];
    $this->queued = [];
    $this->retracked = [];
  }

  /**
   * Build a container holding the given indexes.
   *
   * @param \Drupal\search_api\IndexInterface[] $indexes
   *   Indexes that Index::loadMultiple() should return.
   */
  protected function setUpContainer(array $indexes = []): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($indexes);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $repository->method('getEntityTypeFromClass')->willReturn('search_api_index');

    $helper = $this->createMock(SearchApiAlgoliaHelper::class);
    $helper->method('entityDelete')->willReturnCallback(
      function ($entity) {
        $this->queued[] = $entity->id();
      }
    );

    $tracking_manager = $this->createMock(ContentEntityTrackingManager::class);
    $tracking_manager->method('trackEntityChange')->willReturnCallback(
      function ($entity) {
        $this->retracked[] = $entity->id();
      }
    );

    $container = new ContainerBuilder();
    $container->set('search_api.entity_datasource.tracking_manager', $tracking_manager);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $repository);
    $container->set('search_api_algolia.helper', $helper);
    \Drupal::setContainer($container);
  }

  /**
   * Register a database whose node query returns the given rows.
   *
   * @param array $rows
   *   Each row as an nid/langcode pair, as the node_field_data query returns.
   */
  protected function setUpDatabase(array $rows): void {
    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn(
      array_map(fn(array $row) => (object) $row, $rows)
    );

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->with('node_field_data', 'n')->willReturn($select);

    \Drupal::getContainer()->set('database', $connection);
  }

  /**
   * Build an index that the module counts as an active Algolia index.
   *
   * @param string $id
   *   Index ID, used to record what it was asked to track.
   * @param bool $active
   *   Whether the index is enabled on an enabled Algolia server.
   */
  protected function mockIndex(string $id, bool $active = TRUE): IndexInterface {
    $backend = $this->createMock(SearchApiAlgoliaBackend::class);
    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackend')->willReturn($backend);

    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn($id);
    $index->method('getOption')->willReturnCallback(
      fn($name) => $name === 'algolia_index_name' ? 'archaeology' : NULL
    );
    $index->method('status')->willReturn($active);
    $index->method('isReadOnly')->willReturn(FALSE);
    $index->method('isServerEnabled')->willReturn($active);
    $index->method('getServerInstance')->willReturn($server);
    // filterValidItemIds() returns every ID when the datasource is unknown.
    $index->method('isValidDatasource')->willReturn(FALSE);
    $index->method('trackItemsUpdated')->willReturnCallback(
      function ($datasource_id, array $ids) use ($id) {
        $this->tracked[$id] = $ids;
      }
    );

    return $index;
  }

  /**
   * Build a node whose anonymous view access is known.
   */
  protected function mockNode(int $nid, bool $anonymous_can_view): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn($nid);
    $node->method('access')->willReturn($anonymous_can_view);
    return $node;
  }

  /**
   * Build a form state whose storage holds the given node type.
   */
  protected function mockFormState(string $node_type): FormStateInterface {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getStorage')->willReturn(['node_type' => $node_type]);
    return $form_state;
  }

  /**
   * The per-node access form queues a newly restricted record for removal.
   */
  public function testRestrictedNodeIsQueuedForRemoval(): void {
    $this->setUpContainer([$this->mockIndex('hs_algolia')]);
    $node = $this->mockNode(7, FALSE);

    hs_algolia_content_access_per_node(['view' => []], $node);

    $this->assertSame([7], $this->queued);
    $this->assertSame([7], $this->retracked);
  }

  /**
   * Content the anonymous user can still see is not queued for removal.
   */
  public function testVisibleNodeIsNotQueuedForRemoval(): void {
    $this->setUpContainer([$this->mockIndex('hs_algolia')]);
    $node = $this->mockNode(7, TRUE);

    hs_algolia_content_access_per_node(['view' => ['anonymous']], $node);

    $this->assertSame([], $this->queued);
    // Still re-tracked, so content that just became public gets indexed.
    $this->assertSame([7], $this->retracked);
  }

  /**
   * A site without Algolia enabled does no work at all.
   */
  public function testInactiveIndexQueuesNothing(): void {
    $this->setUpContainer([$this->mockIndex('hs_algolia', FALSE)]);
    $node = $this->mockNode(7, FALSE);

    hs_algolia_content_access_per_node(['view' => []], $node);

    $this->assertSame([], $this->queued);
    $this->assertSame([], $this->retracked);
  }

  /**
   * The bundle access form runs the re-track handler after its own.
   *
   * The form shape matches what ContentAccessAdminSettingsForm builds: a
   * submit button at $form['submit'] with no handlers of its own, and core's
   * '::submitForm' already in $form['#submit'].
   */
  public function testBundleFormRunsRetrackAfterItsOwnHandler(): void {
    $form = [
      '#submit' => ['::submitForm'],
      'submit' => ['#type' => 'submit', '#value' => 'Submit', '#weight' => 10],
    ];
    $form_state = $this->mockFormState('hs_basic_page');

    hs_algolia_form_content_access_admin_settings_alter($form, $form_state);

    $this->assertSame(
      ['::submitForm', 'hs_algolia_content_access_bundle_submit'],
      $form['#submit']
    );
  }

  /**
   * The submit button keeps no handlers of its own.
   *
   * Giving that button a #submit would replace the form's handler rather than
   * add to it, so the form would stop saving its settings.
   */
  public function testBundleFormSubmitButtonIsLeftAlone(): void {
    $form = [
      '#submit' => ['::submitForm'],
      'submit' => ['#type' => 'submit', '#value' => 'Submit', '#weight' => 10],
    ];
    $form_state = $this->mockFormState('hs_basic_page');

    hs_algolia_form_content_access_admin_settings_alter($form, $form_state);

    $this->assertArrayNotHasKey('#submit', $form['submit']);
  }

  /**
   * The bundle handler re-tracks the content type the form was editing.
   */
  public function testBundleSubmitRetracksItsContentType(): void {
    $index = $this->mockIndex('hs_algolia');
    $this->setUpContainer([$index]);
    $this->setUpDatabase([['nid' => 4, 'langcode' => 'en']]);

    hs_algolia_content_access_bundle_submit([], $this->mockFormState('hs_news'));

    $this->assertSame(['4:en'], $this->tracked['hs_algolia']);
  }

  /**
   * Re-tracking a bundle marks every one of its items for reindexing.
   */
  public function testBundleRetrackMarksItemsForReindex(): void {
    $index = $this->mockIndex('hs_algolia');
    $this->setUpContainer([$index]);
    $this->setUpDatabase([
      ['nid' => 3, 'langcode' => 'en'],
      ['nid' => 9, 'langcode' => 'en'],
    ]);

    hs_algolia_retrack_bundles(['hs_basic_page']);

    $this->assertSame(['3:en', '9:en'], $this->tracked['hs_algolia']);
  }

  /**
   * Re-tracking without an active Algolia index never queries the database.
   */
  public function testBundleRetrackDoesNothingWithoutActiveIndex(): void {
    $this->setUpContainer([$this->mockIndex('hs_algolia', FALSE)]);

    hs_algolia_retrack_bundles(['hs_basic_page']);

    $this->assertSame([], $this->tracked);
  }

}
