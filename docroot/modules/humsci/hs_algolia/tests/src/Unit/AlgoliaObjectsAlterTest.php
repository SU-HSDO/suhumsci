<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
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
      fn($plain) => $map[$plain] ?? ''
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
  protected function mockNode(): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');
    return $entity;
  }

  /**
   * Build an index whose fields report the given property paths.
   *
   * @param array $property_paths
   *   Field name keyed to property path.
   *
   * @return \Drupal\search_api\IndexInterface
   *   Mocked index.
   */
  protected function mockIndex(array $property_paths = []): IndexInterface {
    $index = $this->createMock(IndexInterface::class);
    $index->method('getField')->willReturnCallback(function ($name) use ($property_paths) {
      if (!isset($property_paths[$name])) {
        return NULL;
      }
      $field = $this->createMock(FieldInterface::class);
      $field->method('getPropertyPath')->willReturn($property_paths[$name]);
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
    $token = '[node:field_a:entity:url]';
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

}
