<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\AlgoliaSearch\Model\Search\UpdatedAtResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\hs_algolia\Plugin\search_api\backend\HsAlgoliaBackend;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests that one site can never empty the shared Training Hub index.
 *
 * Search API clears an index when it is disabled, cleared, rebuilt, deleted
 * or moved to another server. The contributed backend answers every one of
 * those by emptying the whole Algolia index.
 */
#[Group('hs_algolia')]
class SharedIndexClearTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
  }

  /**
   * Build the backend around a client, without its plugin constructor.
   */
  protected function buildBackend(SearchClient $client, string $site_path = 'sites/hs_sandbox'): HsAlgoliaBackend {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(FALSE);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturn($config);

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('isMultilingual')->willReturn(FALSE);

    $backend = (new \ReflectionClass(HsAlgoliaBackend::class))->newInstanceWithoutConstructor();
    foreach ([
      'algoliaClient' => $client,
      'configFactory' => $config_factory,
      'languageManager' => $language_manager,
      'logger' => $this->createMock(LoggerInterface::class),
      'sitePath' => $site_path,
    ] as $property => $value) {
      $reflection = new \ReflectionProperty($backend, $property);
      $reflection->setValue($backend, $value);
    }
    return $backend;
  }

  /**
   * Build an index with the given ID and Algolia index name.
   */
  protected function mockIndex(string $id, string $index_name): IndexInterface {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn($id);
    $index->method('get')->willReturnCallback(fn($key) => $key === 'id' ? $id : NULL);
    $index->method('getOption')->willReturnCallback(
      fn($name) => $name === 'algolia_index_name' ? $index_name : NULL
    );
    return $index;
  }

  /**
   * Build a client whose index declares the given facets.
   */
  protected function mockClient(array $facets): SearchClient {
    $client = $this->createMock(SearchClient::class);
    $client->method('getSettings')->willReturn(['attributesForFaceting' => $facets]);
    return $client;
  }

  /**
   * Clearing the shared index removes only this site's records.
   */
  public function testFederatedClearDeletesOnlyThisSite(): void {
    $client = $this->mockClient(['site_id', 'searchable(site_name)']);
    $client->expects($this->never())->method('clearObjects');
    $client->expects($this->once())
      ->method('deleteBy')
      ->with('hsdp_trainings_stage', ['filters' => 'site_id:"hs_sandbox"'])
      ->willReturn(['taskID' => 1]);

    $this->buildBackend($client)
      ->deleteAllIndexItems($this->mockIndex('hs_algolia_trainings', 'hsdp_trainings_stage'));
  }

  /**
   * A site_id declared filter only or searchable still counts as facetable.
   */
  public function testFederatedClearAcceptsAnyFacetMode(): void {
    $client = $this->mockClient(['filterOnly(site_id)']);
    $client->expects($this->never())->method('clearObjects');
    $client->expects($this->once())->method('deleteBy')->willReturn(['taskID' => 1]);

    $this->buildBackend($client, 'sites/hs_sandbox_1')
      ->deleteAllIndexItems($this->mockIndex('hs_algolia_trainings', 'hsdp_trainings_stage'));
  }

  /**
   * Without a site_id facet the filter would match nothing, so stop loudly.
   *
   * Search API logs the exception and retries the removal as a server task.
   */
  public function testFederatedClearRefusesWithoutSiteFacet(): void {
    $client = $this->mockClient([]);
    $client->expects($this->never())->method('clearObjects');
    $client->expects($this->never())->method('deleteBy');

    $this->expectException(SearchApiException::class);
    $this->expectExceptionMessage('configure-federated');
    $this->buildBackend($client)
      ->deleteAllIndexItems($this->mockIndex('hs_algolia_trainings', 'hsdp_trainings_stage'));
  }

  /**
   * A site's own index is still emptied exactly as the contrib backend does.
   */
  public function testPerSiteClearIsUnchanged(): void {
    $response = $this->createMock(UpdatedAtResponse::class);
    $response->method('getTaskID')->willReturn(1);
    $client = $this->createMock(SearchClient::class);
    $client->expects($this->once())->method('clearObjects')->with('archaeology')->willReturn($response);
    $client->expects($this->never())->method('deleteBy');
    $client->expects($this->never())->method('getSettings');

    $this->buildBackend($client)
      ->deleteAllIndexItems($this->mockIndex('hs_algolia', 'archaeology'));
  }

  /**
   * The Algolia backend plugin is swapped for the safe subclass.
   */
  public function testBackendClassIsReplaced(): void {
    $definitions = [
      'search_api_algolia' => ['class' => 'Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend'],
      'search_api_db' => ['class' => 'Drupal\search_api_db\Plugin\search_api\backend\Database'],
    ];

    hs_algolia_search_api_backend_info_alter($definitions);

    $this->assertSame(HsAlgoliaBackend::class, $definitions['search_api_algolia']['class']);
    $this->assertSame('Drupal\search_api_db\Plugin\search_api\backend\Database', $definitions['search_api_db']['class']);
  }

}
