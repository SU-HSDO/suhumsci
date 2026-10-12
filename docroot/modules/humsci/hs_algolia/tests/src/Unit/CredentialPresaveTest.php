<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\ServerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that Algolia credentials never reach exportable configuration.
 *
 * The credentials and the Algolia index name are runtime overrides, so active
 * configuration holds empty strings. Saving the Search API server or index
 * through the admin UI writes the overridden values back into active config,
 * which a configuration export would then carry into the repository. These
 * presave hooks put the empty strings back.
 */
#[Group('hs_algolia')]
class CredentialPresaveTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
  }

  /**
   * Build a server whose backend_config is readable and writable.
   *
   * @param string $id
   *   Server ID.
   * @param array $backend_config
   *   Starting backend configuration.
   * @param array $saved
   *   Filled with whatever the hook writes back.
   */
  protected function mockServer(string $id, array $backend_config, array &$saved): ServerInterface {
    $server = $this->createMock(ServerInterface::class);
    $server->method('id')->willReturn($id);
    $server->method('get')->willReturnCallback(
      fn($name) => $name === 'backend_config' ? $backend_config : NULL
    );
    $server->method('set')->willReturnCallback(
      function ($name, $value) use (&$saved, $server) {
        $saved[$name] = $value;
        return $server;
      }
    );
    return $server;
  }

  /**
   * Build an index whose options are readable and writable.
   *
   * @param string $id
   *   Index ID.
   * @param array $options
   *   Starting options.
   * @param array $saved
   *   Filled with whatever the hook writes back.
   */
  protected function mockIndex(string $id, array $options, array &$saved): IndexInterface {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn($id);
    $index->method('getOptions')->willReturn($options);
    $index->method('setOptions')->willReturnCallback(
      function (array $value) use (&$saved, $index) {
        $saved = $value;
        return $index;
      }
    );
    return $index;
  }

  /**
   * Saving the server never writes the Algolia credentials to config.
   */
  public function testServerCredentialsAreBlanked(): void {
    $saved = [];
    $server = $this->mockServer('hs_algolia', [
      'application_id' => 'APP123',
      'api_key' => 'secret-write-key',
    ], $saved);

    hs_algolia_search_api_server_presave($server);

    $this->assertSame('', $saved['backend_config']['application_id']);
    $this->assertSame('', $saved['backend_config']['api_key']);
  }

  /**
   * Blanking the credentials leaves the rest of the backend settings alone.
   */
  public function testOtherBackendSettingsSurvive(): void {
    $saved = [];
    $server = $this->mockServer('hs_algolia', [
      'application_id' => 'APP123',
      'api_key' => 'secret-write-key',
      'disable_truncate' => TRUE,
    ], $saved);

    hs_algolia_search_api_server_presave($server);

    $this->assertTrue($saved['backend_config']['disable_truncate']);
  }

  /**
   * Another site's Search API server is left untouched.
   */
  public function testUnrelatedServerIsNotTouched(): void {
    $saved = [];
    $server = $this->mockServer('some_other_server', [
      'application_id' => 'APP123',
      'api_key' => 'secret-write-key',
    ], $saved);

    hs_algolia_search_api_server_presave($server);

    $this->assertSame([], $saved);
  }

  /**
   * Saving the index never writes the Algolia index name to config.
   */
  public function testIndexNameIsBlanked(): void {
    $saved = [];
    $index = $this->mockIndex('hs_algolia', [
      'algolia_index_name' => 'archaeology',
      'cron_limit' => 200,
    ], $saved);

    hs_algolia_search_api_index_presave($index);

    $this->assertSame('', $saved['algolia_index_name']);
  }

  /**
   * Blanking the index name leaves the rest of the options alone.
   */
  public function testOtherIndexOptionsSurvive(): void {
    $saved = [];
    $index = $this->mockIndex('hs_algolia', [
      'algolia_index_name' => 'archaeology',
      'cron_limit' => 200,
    ], $saved);

    hs_algolia_search_api_index_presave($index);

    $this->assertSame(200, $saved['cron_limit']);
  }

  /**
   * Another index on the site is left untouched.
   */
  public function testUnrelatedIndexIsNotTouched(): void {
    $saved = [];
    $index = $this->mockIndex('default_index', [
      'algolia_index_name' => 'archaeology',
    ], $saved);

    hs_algolia_search_api_index_presave($index);

    $this->assertSame([], $saved);
  }

}
