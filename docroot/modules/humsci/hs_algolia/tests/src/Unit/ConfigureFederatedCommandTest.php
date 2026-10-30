<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\AlgoliaSearch\Exceptions\ExceededRetriesException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hs_algolia\Drush\Commands\HsAlgoliaCommands;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drupal\Tests\UnitTestCase;
use Drush\Log\DrushLoggerManager;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the settings pushed to the shared Training Hub index.
 */
#[Group('hs_algolia')]
class ConfigureFederatedCommandTest extends UnitTestCase {

  /**
   * Every facet the Training Hub uses is declared, in the expected mode.
   */
  public function testPrimaryDeclaresFacets(): void {
    $settings = HsAlgoliaCommands::buildFederatedSettings('hsdp_trainings_local');
    $facets = $settings['hsdp_trainings_local']['attributesForFaceting'];

    $this->assertContains('site_id', $facets, 'Filtering by site needs the stable site key.');
    $this->assertContains('filterOnly(nodate)', $facets);
    foreach (['site_name', 'training_name', 'provider', 'audience', 'product', 'unit'] as $attribute) {
      $this->assertContains("searchable($attribute)", $facets);
    }
    $this->assertContains('delivery_method', $facets);
  }

  /**
   * Sorting by start date gets one replica per direction.
   */
  public function testDateSortReplicas(): void {
    $settings = HsAlgoliaCommands::buildFederatedSettings('hsdp_trainings_stage');

    $this->assertSame(
      ['hsdp_trainings_stage', 'hsdp_trainings_stage_date_start_asc', 'hsdp_trainings_stage_date_start_desc'],
      array_keys($settings),
      'The primary must come first, because its settings create the replicas.'
    );
    $this->assertSame(
      ['hsdp_trainings_stage_date_start_asc', 'hsdp_trainings_stage_date_start_desc'],
      $settings['hsdp_trainings_stage']['replicas']
    );
    $this->assertSame('asc(date_start)', $settings['hsdp_trainings_stage_date_start_asc']['ranking'][0]);
    $this->assertSame('desc(date_start)', $settings['hsdp_trainings_stage_date_start_desc']['ranking'][0]);

    // Replicas keep Algolia's relevance criteria after the sort.
    $this->assertContains('typo', $settings['hsdp_trainings_stage_date_start_asc']['ranking']);

    // Facets work on a sorted result list too.
    $this->assertSame(
      $settings['hsdp_trainings_stage']['attributesForFaceting'],
      $settings['hsdp_trainings_stage_date_start_desc']['attributesForFaceting']
    );
  }

  /**
   * Replica names follow the environment, so environments never share one.
   */
  public function testReplicasAreEnvironmentScoped(): void {
    $prod = array_keys(HsAlgoliaCommands::buildFederatedSettings('hsdp_trainings_prod'));
    $stage = array_keys(HsAlgoliaCommands::buildFederatedSettings('hsdp_trainings_stage'));

    $this->assertEmpty(array_intersect($prod, $stage));
  }

  /**
   * Build the command around a federated index.
   *
   * @param string $index_name
   *   Overridden Algolia index name.
   * @param array $backend_config
   *   Overridden server backend configuration.
   * @param \Algolia\AlgoliaSearch\Api\SearchClient|null $client
   *   Client the backend returns.
   * @param string $domain
   *   The site's canonical domain.
   * @param \Drush\Log\DrushLoggerManager|null $logger
   *   Logger the command writes to.
   */
  protected function buildCommand(string $index_name, array $backend_config, ?SearchClient $client = NULL, string $domain = 'https://example.stanford.edu', ?DrushLoggerManager $logger = NULL): HsAlgoliaCommands {
    $backend = $this->createMock(SearchApiAlgoliaBackend::class);
    $backend->method('getConfiguration')->willReturn($backend_config);
    $backend->method('getAlgolia')->willReturn($client ?? $this->createMock(SearchClient::class));

    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackend')->willReturn($backend);

    $index = $this->createMock(IndexInterface::class);
    $index->method('getOption')->with('algolia_index_name')->willReturn($index_name);
    $index->method('hasValidServer')->willReturn(TRUE);
    $index->method('getServerInstance')->willReturn($server);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('hs_algolia_trainings')->willReturn($index);

    $manager = $this->createMock(EntityTypeManagerInterface::class);
    $manager->method('getStorage')->with('search_api_index')->willReturn($storage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('domain')->willReturn($domain);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('domain_301_redirect.settings')->willReturn($config);

    $command = new HsAlgoliaCommands($manager, $config_factory);
    $command->setLogger($logger ?? $this->createMock(DrushLoggerManager::class));
    return $command;
  }

  /**
   * Build a client that records every settings call and wait.
   *
   * @param array $calls
   *   Filled with each call, in order.
   * @param \Throwable|null $wait_error
   *   Thrown by the wait, when given.
   */
  protected function recordingClient(array &$calls, ?\Throwable $wait_error = NULL): SearchClient {
    $client = $this->createMock(SearchClient::class);
    $client->method('setSettings')->willReturnCallback(
      function ($name, $settings, $forward) use (&$calls) {
        $calls[] = ['set', $name, $forward];
        return ['taskID' => count($calls)];
      }
    );
    $client->method('waitForTask')->willReturnCallback(
      function ($name, $task_id, $options, $max_retries, $timeout) use (&$calls, $wait_error) {
        $calls[] = ['wait', $name, $task_id, $max_retries, $timeout];
        if ($wait_error) {
          throw $wait_error;
        }
        return [];
      }
    );
    return $client;
  }

  /**
   * Only the primary is waited on, and that wait is bounded.
   *
   * The replicas exist once the primary's settings are applied, so their own
   * settings need no wait.
   */
  public function testSettingsArePushedInOrder(): void {
    $calls = [];
    $this->buildCommand('hsdp_trainings_local', [
      'application_id' => 'APP',
      'api_key' => 'write-key',
    ], $this->recordingClient($calls))->configureFederated();

    $this->assertSame([
      ['set', 'hsdp_trainings_local', TRUE],
      ['wait', 'hsdp_trainings_local', 1, HsAlgoliaCommands::WAIT_MAX_RETRIES, HsAlgoliaCommands::WAIT_MAX_INTERVAL],
      ['set', 'hsdp_trainings_local_date_start_asc', FALSE],
      ['set', 'hsdp_trainings_local_date_start_desc', FALSE],
    ], $calls);
  }

  /**
   * A site without a canonical domain is warned, and the settings still go.
   */
  public function testMissingCanonicalDomainWarns(): void {
    $calls = [];
    $logger = $this->createMock(DrushLoggerManager::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('canonical domain'));

    $this->buildCommand('hsdp_trainings_local', [
      'application_id' => 'APP',
      'api_key' => 'write-key',
    ], $this->recordingClient($calls), '', $logger)->configureFederated();

    $this->assertCount(4, $calls);
  }

  /**
   * A site with a canonical domain gets no warning.
   */
  public function testCanonicalDomainDoesNotWarn(): void {
    $calls = [];
    $logger = $this->createMock(DrushLoggerManager::class);
    $logger->expects($this->never())->method('warning');

    $this->buildCommand('hsdp_trainings_local', [
      'application_id' => 'APP',
      'api_key' => 'write-key',
    ], $this->recordingClient($calls), 'https://economics.stanford.edu', $logger)->configureFederated();
  }

  /**
   * A stalled primary stops the run loudly, before any replica is touched.
   */
  public function testStalledPrimaryFailsBeforeReplicas(): void {
    $calls = [];
    $client = $this->recordingClient($calls, new ExceededRetriesException('Stopped waiting.'));
    $command = $this->buildCommand('hsdp_trainings_local', [
      'application_id' => 'APP',
      'api_key' => 'write-key',
    ], $client);

    try {
      $command->configureFederated();
      $this->fail('A stalled primary must fail the command.');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('may still apply asynchronously', $e->getMessage());
      $this->assertInstanceOf(ExceededRetriesException::class, $e->getPrevious());
    }

    $this->assertSame(['set', 'wait'], array_column($calls, 0), 'No replica may be configured before its primary.');
  }

  /**
   * Nothing is sent without an environment-scoped index name.
   */
  public function testMissingIndexNameStops(): void {
    $client = $this->createMock(SearchClient::class);
    $client->expects($this->never())->method('setSettings');

    $this->expectException(\RuntimeException::class);
    $this->buildCommand('', ['application_id' => 'APP', 'api_key' => 'write-key'], $client)->configureFederated();
  }

  /**
   * Nothing is sent without credentials.
   */
  public function testMissingCredentialsStop(): void {
    $client = $this->createMock(SearchClient::class);
    $client->expects($this->never())->method('setSettings');

    $this->expectException(\RuntimeException::class);
    $this->buildCommand('hsdp_trainings_local', ['application_id' => '', 'api_key' => ''], $client)->configureFederated();
  }

}
