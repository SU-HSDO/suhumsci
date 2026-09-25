<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hs_algolia\Overrides\ConfigOverrides;
use Drupal\key\KeyInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the runtime overrides that carry per-site Algolia settings.
 */
#[Group('hs_algolia')]
class ConfigOverridesTest extends UnitTestCase {

  /**
   * Build the override service around the given settings and key value.
   *
   * @param array $settings
   *   Values of hs_algolia.settings.
   * @param string|null $key_value
   *   Value the key entity returns, or NULL when the key does not exist.
   */
  protected function buildOverrides(array $settings, ?string $key_value = NULL): ConfigOverrides {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(fn($name) => $settings[$name] ?? NULL);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with(ConfigOverrides::SETTINGS)->willReturn($config);

    $key = NULL;
    if ($key_value !== NULL) {
      $key = $this->createMock(KeyInterface::class);
      $key->method('getKeyValue')->willReturn($key_value);
    }
    $key_storage = $this->createMock(EntityStorageInterface::class);
    $key_storage->method('load')->willReturn($key);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('hasDefinition')->with('key')->willReturn(TRUE);
    $entity_type_manager->method('getStorage')->with('key')->willReturn($key_storage);

    return new ConfigOverrides($config_factory, $entity_type_manager);
  }

  /**
   * A fully configured site overrides credentials and the index name.
   */
  public function testConfiguredSiteOverridesServerAndIndex(): void {
    $overrides = $this->buildOverrides([
      'application_id' => 'APP123',
      'api_key' => 'hs_algolia_write_key',
      'index_name' => 'archaeology',
    ], 'secret-value');

    $result = $overrides->loadOverrides([ConfigOverrides::SERVER, ConfigOverrides::INDEX]);

    $this->assertSame([
      'application_id' => 'APP123',
      'api_key' => 'secret-value',
    ], $result[ConfigOverrides::SERVER]['backend_config']);
    $this->assertSame('archaeology', $result[ConfigOverrides::INDEX]['options']['algolia_index_name']);
  }

  /**
   * A site that has not configured Algolia gets no overrides at all.
   */
  public function testUnconfiguredSiteHasNoOverrides(): void {
    $overrides = $this->buildOverrides([]);

    $this->assertSame([], $overrides->loadOverrides([ConfigOverrides::SERVER, ConfigOverrides::INDEX]));
  }

  /**
   * Credentials are withheld when the key entity is missing.
   */
  public function testMissingKeyWithholdsCredentials(): void {
    $overrides = $this->buildOverrides([
      'application_id' => 'APP123',
      'api_key' => 'deleted_key',
      'index_name' => 'archaeology',
    ]);

    $result = $overrides->loadOverrides([ConfigOverrides::SERVER, ConfigOverrides::INDEX]);

    $this->assertArrayNotHasKey(ConfigOverrides::SERVER, $result);
    $this->assertSame('archaeology', $result[ConfigOverrides::INDEX]['options']['algolia_index_name']);
  }

  /**
   * Half-filled credentials are withheld rather than sent partially.
   */
  public function testPartialCredentialsAreWithheld(): void {
    $overrides = $this->buildOverrides(['application_id' => 'APP123'], 'secret-value');

    $this->assertArrayNotHasKey(ConfigOverrides::SERVER, $overrides->loadOverrides([ConfigOverrides::SERVER]));
  }

  /**
   * Unrelated config names never touch the settings or key storage.
   */
  public function testUnrelatedNamesAreIgnored(): void {
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->never())->method('get');
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');

    $overrides = new ConfigOverrides($config_factory, $entity_type_manager);

    $this->assertSame([], $overrides->loadOverrides(['system.site', 'key.key.hs_algolia_write_key']));
  }

  /**
   * Overridden names are tagged with the settings so caches invalidate.
   */
  public function testCacheabilityTracksSettings(): void {
    $overrides = $this->buildOverrides([]);

    $this->assertContains('config:hs_algolia.settings', $overrides->getCacheableMetadata(ConfigOverrides::SERVER)->getCacheTags());
    $this->assertContains('config:hs_algolia.settings', $overrides->getCacheableMetadata(ConfigOverrides::INDEX)->getCacheTags());
    $this->assertSame([], $overrides->getCacheableMetadata('system.site')->getCacheTags());
  }

}
