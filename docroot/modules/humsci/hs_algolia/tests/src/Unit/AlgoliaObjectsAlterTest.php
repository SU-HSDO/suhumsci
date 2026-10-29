<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Token;
use Drupal\search_api\Item\ItemInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the record shaping applied before objects are sent to Algolia.
 */
#[Group('hs_algolia')]
class AlgoliaObjectsAlterTest extends UnitTestCase {

  /**
   * Token service used by setUpContainer(), when a test supplies one.
   *
   * @var \Drupal\Core\Utility\Token|null
   */
  protected $token;

  /**
   * Database connection used by setUpContainer(), when a test supplies one.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected $database;

  /**
   * Entity type manager used by setUpContainer(), when a test supplies one.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * Logger channel used by setUpContainer(), when a test supplies one.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|null
   */
  protected $logger;

  /**
   * Register a database whose delete query records the conditions it is given.
   *
   * @param array $captured
   *   Filled with the conditions passed to the delete query.
   * @param bool $expect_delete
   *   Whether a delete is expected at all.
   */
  protected function setUpDatabase(array &$captured, bool $expect_delete = TRUE): void {
    $delete = $this->createMock(Delete::class);
    $delete->method('condition')->willReturnCallback(
      function ($field, $value = NULL) use (&$captured, $delete) {
        $captured[$field] = $value;
        return $delete;
      }
    );

    $connection = $this->createMock(Connection::class);
    $connection->expects($expect_delete ? $this->once() : $this->never())
      ->method('delete')
      ->with('search_api_algolia_deleted_items')
      ->willReturn($delete);

    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
  }

  /**
   * Build a container with the services the alter hook reaches for.
   *
   * @param string $canonical_domain
   *   Value of domain_301_redirect.settings:domain.
   * @param string $current_host
   *   Scheme and host of the request being served.
   */
  protected function setUpContainer(string $canonical_domain = '', string $current_host = 'https://internal.example.com'): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('domain')->willReturn($canonical_domain);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('domain_301_redirect.settings')
      ->willReturn($config);

    $request_stack = new RequestStack();
    $request_stack->push(Request::create($current_host . '/some/path'));

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('request_stack', $request_stack);
    $container->set('token', $this->token ?? $this->createMock(Token::class));
    $container->set('database', $this->database ?? $this->createMock(Connection::class));
    $container->setParameter('site.path', 'sites/hs_sandbox');
    $container->set('entity_type.manager', $this->entityTypeManager ?? $this->createMock(EntityTypeManagerInterface::class));
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->logger ?? $this->createMock(LoggerChannelInterface::class));
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);
  }

  /**
   * Register a token service that resolves the given values.
   *
   * @param array $map
   *   Raw token string keyed to the value it resolves to.
   */
  protected function setUpToken(array $map): void {
    $token = $this->createMock(Token::class);
    $token->method('replacePlain')->willReturnCallback(
      function ($plain) use ($map) {
        // token_or resolves a whole "or" construct as a single unit, so a
        // map entry for the raw value wins before anything is scanned.
        if (isset($map[$plain])) {
          return $map[$plain];
        }
        // Otherwise behave like replacePlain() with 'clear': resolve the
        // tokens it recognises, strip the ones it does not, and leave any
        // other bracketed text alone. Core's scan pattern requires a colon,
        // so "[1]" and "[PDF]" are not tokens but "[Smith:2020]" is.
        return preg_replace_callback(
          '/\[[^\s\[\]:]+:[^\[\]]*\]/',
          fn($match) => $map[$match[0]] ?? '',
          $plain
        );
      }
    );
    $this->token = $token;
  }

  /**
   * Build a Search API item wrapping the given entity.
   */
  protected function mockItem(EntityInterface $entity): ItemInterface {
    $object = $this->createMock(ComplexDataInterface::class);
    $object->method('getValue')->willReturn($entity);
    $item = $this->createMock(ItemInterface::class);
    $item->method('getOriginalObject')->willReturn($object);
    return $item;
  }

  /**
   * Build a node entity stub for token data.
   */
  protected function mockNode(string $bundle = 'hs_basic_page'): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');
    $entity->method('bundle')->willReturn($bundle);
    return $entity;
  }

  /**
   * Register entity view display storage holding the given displays.
   *
   * @param array $displays
   *   Display ID keyed to whether it is enabled. Unlisted displays are missing.
   */
  protected function setUpDisplays(array $displays): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturnCallback(function ($id) use ($displays) {
      if (!isset($displays[$id])) {
        return NULL;
      }
      $display = $this->createMock(EntityViewDisplayInterface::class);
      $display->method('status')->willReturn($displays[$id]);
      return $display;
    });

    $manager = $this->createMock(EntityTypeManagerInterface::class);
    $manager->method('getStorage')->with('entity_view_display')->willReturn($storage);
    $this->entityTypeManager = $manager;
  }

  /**
   * Build an index whose html field renders Training with a view mode.
   */
  protected function mockRenderedIndex(string $view_mode = 'search_index'): IndexInterface {
    return $this->mockIndex(
      ['html' => 'rendered_item', 'title' => 'title'],
      '',
      'hs_algolia_trainings',
      ['html' => ['view_mode' => ['entity:node' => ['hs_training' => $view_mode]]]],
    );
  }

  /**
   * Run the alter hook over two Training records.
   */
  protected function alterTrainingRecords(IndexInterface $index): array {
    $objects = [
      'entity:node/1:en' => ['title' => 'One', 'html' => '<p>Contact: Jane</p>'],
      'entity:node/2:en' => ['title' => 'Two', 'html' => '<p>Contact: Sam</p>'],
    ];
    $items = [
      'entity:node/1:en' => $this->mockItem($this->mockNode('hs_training')),
      'entity:node/2:en' => $this->mockItem($this->mockNode('hs_training')),
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $index, $items);
    return $objects;
  }

  /**
   * Build an index whose fields report the given property paths.
   *
   * @param array $property_paths
   *   Field name keyed to property path.
   * @param string $index_name
   *   Value of the algolia_index_name option.
   * @param string $id
   *   Search API index ID.
   * @param array $configurations
   *   Field name keyed to field configuration.
   *
   * @return \Drupal\search_api\IndexInterface
   *   Mocked index.
   */
  protected function mockIndex(array $property_paths = [], string $index_name = '', string $id = 'hs_algolia', array $configurations = []): IndexInterface {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn($id);
    $index->method('getOption')->willReturnCallback(
      fn($name) => $name === 'algolia_index_name' ? $index_name : NULL
    );
    $index->method('getField')->willReturnCallback(function ($name) use ($property_paths, $configurations) {
      if (!isset($property_paths[$name])) {
        return NULL;
      }
      $field = $this->createMock(FieldInterface::class);
      $field->method('getPropertyPath')->willReturn($property_paths[$name]);
      $field->method('getConfiguration')->willReturn($configurations[$name] ?? []);
      return $field;
    });
    return $index;
  }

  /**
   * Tracking fields are stripped and the title is moved to the front.
   */
  public function testRecordIsCleanedAndTitleLeads() {
    $this->setUpContainer();

    $objects = [
      [
        'url' => 'https://example.com/node/1',
        'search_api_datasource' => 'entity:node',
        'status' => TRUE,
        'title' => 'A page',
      ],
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertArrayNotHasKey('search_api_datasource', $objects[0]);
    $this->assertArrayNotHasKey('status', $objects[0]);
    $this->assertSame('title', array_key_first($objects[0]));
    $this->assertSame('A page', $objects[0]['title']);
  }

  /**
   * A single taxonomy term value is always sent as an array.
   */
  public function testSingleTaxonomyValueBecomesArray() {
    $this->setUpContainer();

    $objects = [
      [
        'news_category' => 'Announcements',
        'title' => 'A news item',
      ],
    ];
    $index = $this->mockIndex([
      'news_category' => 'field_hs_news_categories:entity:name',
      'title' => 'title',
    ]);
    hs_algolia_search_api_algolia_objects_alter($objects, $index, []);

    $this->assertSame(['Announcements'], $objects[0]['news_category']);
    // A plain string field is left alone.
    $this->assertSame('A news item', $objects[0]['title']);
  }

  /**
   * URLs built during cron are rewritten to the canonical public domain.
   */
  public function testUrlsAreRewrittenToCanonicalDomain() {
    $this->setUpContainer('https://news.stanford.edu', 'https://internal.example.com');

    $objects = [
      [
        'title' => 'A page',
        'url' => 'https://internal.example.com/node/1',
        'html' => '<a href="https://internal.example.com/other">Other</a>',
      ],
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame('https://news.stanford.edu/node/1', $objects[0]['url']);
    $this->assertStringContainsString('https://news.stanford.edu/other', $objects[0]['html']);
  }

  /**
   * A canonical domain stored without a scheme still resolves.
   */
  public function testCanonicalDomainWithoutSchemeIsNormalized() {
    $this->setUpContainer('news.stanford.edu', 'https://internal.example.com');

    $objects = [['title' => 'A page', 'url' => 'https://internal.example.com/node/1']];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame('https://news.stanford.edu/node/1', $objects[0]['url']);
  }

  /**
   * With no canonical domain configured, URLs are left untouched.
   */
  public function testNoCanonicalDomainLeavesUrlsAlone() {
    $this->setUpContainer('', 'https://internal.example.com');

    $objects = [['title' => 'A page', 'url' => 'https://internal.example.com/node/1']];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame('https://internal.example.com/node/1', $objects[0]['url']);
  }

  /**
   * Oversized records are trimmed under the limit when trimming is enabled.
   */
  public function testOversizedRecordIsTrimmed() {
    $this->setUpContainer();
    new Settings(['hs_algolia_trim_html' => TRUE]);

    $objects = [
      [
        'title' => 'A long page',
        'html' => str_repeat('word ', 5000),
      ],
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertLessThanOrEqual(
      HS_ALGOLIA_RECORD_LIMIT,
      strlen(json_encode($objects[0], JSON_UNESCAPED_SLASHES))
    );
    // The beginning of the content survives.
    $this->assertStringStartsWith('word word', $objects[0]['html']);
  }

  /**
   * Records are left at full length when trimming is not enabled.
   */
  public function testRecordIsNotTrimmedByDefault() {
    $this->setUpContainer();
    new Settings([]);

    $html = str_repeat('word ', 5000);
    $objects = [['title' => 'A long page', 'html' => $html]];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame($html, $objects[0]['html']);
  }

  /**
   * Trimming a record with no rendered HTML terminates.
   */
  public function testTrimWithoutHtmlDoesNotLoop() {
    $this->setUpContainer();
    new Settings(['hs_algolia_trim_html' => TRUE]);

    $objects = [['title' => str_repeat('a', 20000)]];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertArrayNotHasKey('html', $objects[0]);
  }

  /**
   * A custom value field's tokens are resolved before the record is sent.
   */
  public function testCustomValueTokensAreResolved() {
    $token = '[node:field_a:entity:url|node:field_b:entity:url]';
    $this->setUpToken([$token => 'https://internal.example.com/image.jpg']);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['title' => 'Page', 'image' => $token]];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['image' => 'custom_value']), $items);

    $this->assertSame('https://internal.example.com/image.jpg', $objects['entity:node/1:en']['image']);
  }

  /**
   * A resolved token is still rewritten to the canonical domain.
   */
  public function testResolvedTokenIsRewrittenToCanonicalDomain() {
    $token = '[node:field_a:entity:url|node:field_b:entity:url]';
    $this->setUpToken([$token => 'https://internal.example.com/image.jpg']);
    $this->setUpContainer('https://history.stanford.edu');

    $objects = ['entity:node/1:en' => ['image' => $token]];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['image' => 'custom_value']), $items);

    $this->assertSame('https://history.stanford.edu/image.jpg', $objects['entity:node/1:en']['image']);
  }

  /**
   * A token that resolves to nothing drops the attribute entirely.
   */
  public function testUnresolvedTokenIsOmitted() {
    $token = '[node:field_a:entity:url|node:field_b:entity:url]';
    $this->setUpToken([]);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['title' => 'Page', 'image' => $token]];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['image' => 'custom_value']), $items);

    $this->assertArrayNotHasKey('image', $objects['entity:node/1:en']);
    $this->assertSame('Page', $objects['entity:node/1:en']['title']);
  }

  /**
   * Fields that are not custom values are never sent to the token service.
   */
  public function testNonCustomValueFieldsAreNotTokenised() {
    $this->setUpToken([]);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['summary' => 'Text with [brackets] in it']];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['summary' => 'summary']), $items);

    $this->assertSame('Text with [brackets] in it', $objects['entity:node/1:en']['summary']);
  }

  /**
   * Bracketed prose in a custom value is not mistaken for a token.
   * 
   */
  public function testCustomValueBracketedProseIsPreserved() {
    $summary = 'Chapter [1] of the survey, also released as [PDF].';
    $this->setUpToken([]);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['summary' => $summary]];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['summary' => 'custom_value']), $items);

    $this->assertSame($summary, $objects['entity:node/1:en']['summary']);
  }

  /**
   * A bracketed citation in a custom value survives intact.
   *
   * "[Smith:2020]" matches the token service's scan pattern, so a second
   * token pass over resolved prose would silently delete it.
   */
  public function testCustomValueCitationIsPreserved() {
    $summary = 'Builds on earlier fieldwork [Smith:2020] in the region.';
    $this->setUpToken([]);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['summary' => $summary]];
    $items = ['entity:node/1:en' => $this->mockItem($this->mockNode())];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(['summary' => 'custom_value']), $items);

    $this->assertSame($summary, $objects['entity:node/1:en']['summary']);
  }

  /**
   * Training Hub records carry the site directory as a stable site key.
   */
  public function testFederatedRecordsCarrySiteId() {
    $this->setUpContainer();

    $objects = [
      'entity:node/1:en' => ['title' => 'One', 'site_name' => 'Sandbox'],
      'entity:node/2:en' => ['title' => 'Two', 'site_name' => 'Sandbox'],
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex([], '', 'hs_algolia_trainings'), []);

    $this->assertSame('hs_sandbox', $objects['entity:node/1:en']['site_id']);
    $this->assertSame('hs_sandbox', $objects['entity:node/2:en']['site_id']);
    // The title still leads.
    $this->assertSame('title', array_key_first($objects['entity:node/1:en']));
  }

  /**
   * Training Hub facet values from different sites merge into one.
   */
  public function testFederatedFacetValuesAreNormalized() {
    $this->setUpContainer();

    $objects = [
      'entity:node/1:en' => [
        'title' => '  Excel   101 ',
        'summary' => 'Learn  Excel',
        'html' => '<p>Excel  Basics</p>',
        'site_name' => 'Economics  Department',
        'unit' => ['IT Services', ' it  services', "IT\u{00A0}Services ", 'Finance'],
        'product' => 'Microsoft  EXCEL ',
        'training_name' => ['Excel 101'],
        'provider' => ['  LinkedIn Learning'],
        'audience' => ['Staff', 'staff'],
        'delivery_method' => "  In  person\n",
        'location' => 'Building  160 ',
      ],
    ];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex([], '', 'hs_algolia_trainings'), []);
    $object = $objects['entity:node/1:en'];

    $this->assertSame(['it services', 'finance'], $object['unit']);
    $this->assertSame('microsoft excel', $object['product']);
    $this->assertSame(['excel 101'], $object['training_name']);
    $this->assertSame(['linkedin learning'], $object['provider']);
    $this->assertSame(['staff'], $object['audience']);
    $this->assertSame('in person', $object['delivery_method']);

    // Display text is never rewritten, including location, which is not a
    // facet and is shown as entered.
    $this->assertSame('Building  160 ', $object['location']);
    $this->assertSame('  Excel   101 ', $object['title']);
    $this->assertSame('Learn  Excel', $object['summary']);
    $this->assertSame('<p>Excel  Basics</p>', $object['html']);
    $this->assertSame('Economics  Department', $object['site_name']);
  }

  /**
   * A site's own index keeps its terms exactly as editors entered them.
   */
  public function testPerSiteFacetValuesAreNotNormalized() {
    $this->setUpContainer();

    $objects = [['title' => 'One', 'unit' => ['IT  Services']]];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame(['IT  Services'], $objects[0]['unit']);
  }

  /**
   * A site's own index holds only its own records, so needs no site key.
   */
  public function testPerSiteRecordsHaveNoSiteId() {
    $this->setUpContainer();

    $objects = [['title' => 'One']];
    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertArrayNotHasKey('site_id', $objects[0]);
  }

  /**
   * Rendered HTML is withheld when its display is missing.
   *
   * Drupal would have rendered the default display instead, which shows
   * Training contacts. One warning covers the whole batch.
   */
  public function testMissingDisplayWithholdsRenderedHtml() {
    $this->setUpDisplays([]);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with($this->anything(), $this->callback(
        fn($context) => $context['@displays'] === 'node.hs_training.search_index'
      ));
    $this->logger = $logger;
    $this->setUpContainer();

    $objects = $this->alterTrainingRecords($this->mockRenderedIndex());

    foreach ($objects as $object) {
      $this->assertArrayNotHasKey('html', $object);
      $this->assertArrayHasKey('title', $object);
    }
  }

  /**
   * A disabled display falls back to the default display too.
   */
  public function testDisabledDisplayWithholdsRenderedHtml() {
    $this->setUpDisplays(['node.hs_training.search_index' => FALSE]);
    $this->setUpContainer();

    $objects = $this->alterTrainingRecords($this->mockRenderedIndex());

    $this->assertArrayNotHasKey('html', $objects['entity:node/1:en']);
  }

  /**
   * Rendered HTML is sent when its display exists and is enabled.
   */
  public function testPresentDisplayKeepsRenderedHtml() {
    $this->setUpDisplays(['node.hs_training.search_index' => TRUE]);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->never())->method('warning');
    $this->logger = $logger;
    $this->setUpContainer();

    $objects = $this->alterTrainingRecords($this->mockRenderedIndex());

    $this->assertSame('<p>Contact: Jane</p>', $objects['entity:node/1:en']['html']);
  }

  /**
   * The default view mode is what a missing display falls back to anyway.
   */
  public function testDefaultViewModeIsNotChecked() {
    $this->setUpDisplays([]);
    $this->setUpContainer();

    $objects = $this->alterTrainingRecords($this->mockRenderedIndex('default'));

    $this->assertArrayHasKey('html', $objects['entity:node/1:en']);
  }

  /**
   * Sending a record cancels any deletion queued for it.
   */
  public function testSendingRecordsCancelsQueuedDeletions() {
    $captured = [];
    $this->setUpDatabase($captured);
    $this->setUpContainer();

    $objects = [
      'entity:node/1:en' => ['objectID' => 'uuid-1', 'title' => 'One'],
      'entity:node/2:en' => ['objectID' => 'uuid-2', 'title' => 'Two'],
    ];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex([], 'history'), []);

    $this->assertSame('history', $captured['index_id']);
    $this->assertSame(['uuid-1', 'uuid-2'], $captured['object_id']);
  }

  /**
   * Nothing is dequeued when the index has no Algolia index name.
   */
  public function testNoDequeueWithoutAnIndexName() {
    $captured = [];
    $this->setUpDatabase($captured, FALSE);
    $this->setUpContainer();

    $objects = ['entity:node/1:en' => ['objectID' => 'uuid-1']];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex(), []);

    $this->assertSame([], $captured);
  }

  /**
   * Nothing is dequeued when there are no records to send.
   */
  public function testNoDequeueWithoutObjects() {
    $captured = [];
    $this->setUpDatabase($captured, FALSE);
    $this->setUpContainer();

    $objects = [];

    hs_algolia_search_api_algolia_objects_alter($objects, $this->mockIndex([], 'history'), []);

    $this->assertSame([], $captured);
  }

}
