<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hs_algolia\SearchStatus;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ServerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests when the Algolia search interface replaces the Drupal search.
 */
#[Group('hs_algolia')]
class SearchStatusTest extends UnitTestCase {

  /**
   * A fully configured site.
   */
  const COMPLETE = [
    'search_enabled' => TRUE,
    'search_api_key' => 'search-key',
    'application_id' => 'APP',
    'index_name' => 'archaeology',
    'api_key' => 'algolia_write_key',
  ];

  /**
   * Build the service around the given settings and entity status.
   *
   * @param array $settings
   *   Values of hs_algolia.settings.
   * @param bool|null $server_enabled
   *   Server status, or NULL when the server is missing.
   * @param bool|null $index_enabled
   *   Index status, or NULL when the index is missing.
   */
  protected function buildStatus(array $settings, ?bool $server_enabled = TRUE, ?bool $index_enabled = TRUE): SearchStatus {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(fn($key) => $settings[$key] ?? NULL);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('hs_algolia.settings')->willReturn($config);

    $server = NULL;
    if ($server_enabled !== NULL) {
      $server = $this->createMock(ServerInterface::class);
      $server->method('status')->willReturn($server_enabled);
    }
    $index = NULL;
    if ($index_enabled !== NULL) {
      $index = $this->createMock(IndexInterface::class);
      $index->method('status')->willReturn($index_enabled);
    }
    $server_storage = $this->createMock(EntityStorageInterface::class);
    $server_storage->method('load')->with('hs_algolia')->willReturn($server);
    $index_storage = $this->createMock(EntityStorageInterface::class);
    $index_storage->method('load')->with('hs_algolia')->willReturn($index);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnMap([
      ['search_api_server', $server_storage],
      ['search_api_index', $index_storage],
    ]);

    return new SearchStatus($config_factory, $entity_type_manager);
  }

  /**
   * A fully configured site with enabled entities searches through Algolia.
   */
  public function testCompleteSiteIsActive(): void {
    $this->assertTrue($this->buildStatus(self::COMPLETE)->isActive());
  }

  /**
   * Any missing piece falls back to the Drupal search.
   */
  #[DataProvider('incompleteProvider')]
  public function testIncompleteSiteIsInactive(array $settings, ?bool $server_enabled, ?bool $index_enabled): void {
    $this->assertFalse($this->buildStatus($settings, $server_enabled, $index_enabled)->isActive());
  }

  /**
   * Sites that must keep the Drupal search.
   */
  public static function incompleteProvider(): array {
    return [
      'interface off' => [['search_enabled' => FALSE] + self::COMPLETE, TRUE, TRUE],
      'setting missing' => [array_diff_key(self::COMPLETE, ['search_enabled' => 0]), TRUE, TRUE],
      'no search key' => [['search_api_key' => ''] + self::COMPLETE, TRUE, TRUE],
      'blank search key' => [['search_api_key' => '  '] + self::COMPLETE, TRUE, TRUE],
      'no application ID' => [['application_id' => ''] + self::COMPLETE, TRUE, TRUE],
      'no index name' => [['index_name' => ''] + self::COMPLETE, TRUE, TRUE],
      'server disabled' => [self::COMPLETE, FALSE, TRUE],
      'index disabled' => [self::COMPLETE, TRUE, FALSE],
      'server missing' => [self::COMPLETE, NULL, TRUE],
      'index missing' => [self::COMPLETE, TRUE, NULL],
    ];
  }

  /**
   * The browser gets the search key and never the write key.
   */
  public function testClientSettingsOmitWriteKey(): void {
    $client = $this->buildStatus(self::COMPLETE)->getClientSettings();

    $this->assertSame([
      'appId' => 'APP',
      'searchKey' => 'search-key',
      'indexName' => 'archaeology',
    ], $client);
    $this->assertNotContains('algolia_write_key', $client);
  }

  /**
   * Turning search on or off, or toggling the entities, invalidates pages.
   */
  public function testCacheTags(): void {
    $tags = $this->buildStatus(self::COMPLETE)->getCacheableMetadata()->getCacheTags();

    $this->assertEqualsCanonicalizing([
      'config:hs_algolia.settings',
      'config:search_api.server.hs_algolia',
      'config:search_api.index.hs_algolia',
    ], $tags);
  }

}
