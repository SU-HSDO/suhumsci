<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\search_api\ServerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the Algolia write key is not printed on the server summary.
 */
#[Group('hs_algolia')]
class ServerSummaryTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';

    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator->method('generateFromRoute')->willReturn('/admin/config/search/algolia');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('url_generator', $url_generator);
    \Drupal::setContainer($container);
  }

  /**
   * Build the preprocess variables for a server summary.
   *
   * @param string|null $backend_id
   *   Backend the server uses, or NULL for no server at all.
   * @param string $api_key
   *   Write key in the server's backend configuration.
   */
  protected function buildVariables(?string $backend_id, string $api_key = 'secret-key'): array {
    $rows = [
      ['data' => [['header' => TRUE, 'data' => 'Status'], 'enabled']],
      ['data' => [['header' => TRUE, 'data' => 'Application ID'], 'APP123']],
      ['data' => [['header' => TRUE, 'data' => 'API Key'], $api_key]],
    ];

    if ($backend_id === NULL) {
      return ['server' => NULL, 'server_info_table' => ['#rows' => $rows]];
    }

    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackendId')->willReturn($backend_id);
    $server->method('getBackendConfig')->willReturn([
      'application_id' => 'APP123',
      'api_key' => $api_key,
    ]);

    return ['server' => $server, 'server_info_table' => ['#rows' => $rows]];
  }

  /**
   * The row holding the write key is replaced with a pointer to the form.
   */
  public function testApiKeyIsMasked(): void {
    $variables = $this->buildVariables('search_api_algolia');
    hs_algolia_preprocess_search_api_server($variables);

    $rows = $variables['server_info_table']['#rows'];
    $this->assertStringNotContainsString('secret-key', (string) $rows[2]['data'][1]);
    $this->assertStringContainsString('/admin/config/search/algolia', (string) $rows[2]['data'][1]);
  }

  /**
   * Rows that do not hold the key are left alone.
   */
  public function testOtherRowsAreUntouched(): void {
    $variables = $this->buildVariables('search_api_algolia');
    hs_algolia_preprocess_search_api_server($variables);

    $rows = $variables['server_info_table']['#rows'];
    $this->assertSame('enabled', $rows[0]['data'][1]);
    $this->assertSame('APP123', $rows[1]['data'][1]);
  }

  /**
   * Servers on other backends are left alone.
   */
  public function testOtherBackendsAreUntouched(): void {
    $variables = $this->buildVariables('search_api_db');
    $expected = $variables['server_info_table'];
    hs_algolia_preprocess_search_api_server($variables);

    $this->assertSame($expected, $variables['server_info_table']);
  }

  /**
   * A server with no credentials configured masks nothing.
   */
  public function testEmptyKeyMasksNothing(): void {
    $variables = $this->buildVariables('search_api_algolia', '');
    $expected = $variables['server_info_table'];
    hs_algolia_preprocess_search_api_server($variables);

    $this->assertSame($expected, $variables['server_info_table']);
  }

  /**
   * Variables without a server entity are ignored.
   */
  public function testMissingServerIsIgnored(): void {
    $variables = $this->buildVariables(NULL);
    $expected = $variables['server_info_table'];
    hs_algolia_preprocess_search_api_server($variables);

    $this->assertSame($expected, $variables['server_info_table']);
  }

}
