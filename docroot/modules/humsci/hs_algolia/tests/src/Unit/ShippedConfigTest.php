<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards the safety promises made by the shipped Algolia configuration.
 *
 * Algolia records are queried from the browser with a public search-only key,
 * so anything indexed is effectively public. These assertions fail loudly if
 * that boundary is ever widened by accident.
 */
#[Group('hs_algolia')]
class ShippedConfigTest extends UnitTestCase {

  /**
   * Content types that must never reach Algolia.
   */
  const EXCLUDED_BUNDLES = ['hs_private_page', 'hs_project', 'hs_training'];

  /**
   * Absolute path to the repository root.
   */
  protected function repoRoot(): string {
    return dirname(__DIR__, 7);
  }

  /**
   * Load a shipped configuration file.
   *
   * @param string $directory
   *   Repository relative directory holding the file.
   * @param string $name
   *   Configuration object name.
   *
   * @return array
   *   Parsed configuration.
   */
  protected function loadConfig(string $directory, string $name): array {
    $path = $this->repoRoot() . '/' . $directory . '/' . $name . '.yml';
    $this->assertFileExists($path);
    return Yaml::parseFile($path);
  }

  /**
   * The platform copy and the module copy must not drift apart.
   *
   * A fresh site installs the module copy, then imports the platform copy over
   * it. A differing UUID makes that import fail outright, and differing values
   * would leave new sites configured differently from existing ones.
   */
  public function testModuleAndPlatformCopiesAreIdentical() {
    foreach (['search_api.server.hs_algolia', 'search_api.index.hs_algolia'] as $name) {
      $this->assertSame(
        file_get_contents($this->repoRoot() . '/config/default/' . $name . '.yml'),
        file_get_contents($this->repoRoot() . '/docroot/modules/humsci/hs_algolia/config/install/' . $name . '.yml'),
        "$name differs between config/default and the module's config/install."
      );
    }
  }

  /**
   * Both entities ship disabled so no site indexes without opting in.
   */
  public function testShipsDisabled() {
    foreach (['search_api.server.hs_algolia', 'search_api.index.hs_algolia'] as $name) {
      $config = $this->loadConfig('config/default', $name);
      $this->assertFalse($config['status'], "$name must ship disabled.");
    }
  }

  /**
   * The server ships without credentials, which stay out of this repository.
   */
  public function testServerShipsWithoutCredentials() {
    $server = $this->loadConfig('config/default', 'search_api.server.hs_algolia');

    $this->assertSame('search_api_algolia', $server['backend']);
    $this->assertSame('', $server['backend_config']['application_id']);
    $this->assertSame('', $server['backend_config']['api_key']);
  }

  /**
   * Only an explicit allow-list of public content types is indexed.
   */
  public function testOnlyPublicBundlesAreIndexed() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');
    $bundles = $index['datasource_settings']['entity:node']['bundles'];

    $this->assertFalse(
      $bundles['default'],
      'Bundles must be an allow-list so new content types are opt-in.'
    );
    $this->assertNotEmpty($bundles['selected']);

    foreach (self::EXCLUDED_BUNDLES as $bundle) {
      $this->assertNotContains($bundle, $bundles['selected'], "$bundle must not be indexed.");
    }
  }

  /**
   * Unpublished content is excluded at index time.
   */
  public function testUnpublishedContentIsExcluded() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');

    $this->assertArrayHasKey(
      'entity_status',
      $index['processor_settings'],
      'The entity_status processor keeps unpublished content out of Algolia.'
    );
  }

  /**
   * Content is rendered as an anonymous visitor, never a privileged role.
   */
  public function testRenderedItemUsesAnonymousRole() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');

    $this->assertSame(
      ['anonymous'],
      $index['field_settings']['html']['configuration']['roles'],
      'Rendering as any other role would push non-public markup into Algolia.'
    );
  }

  /**
   * Node grants are never handed to a third party service.
   */
  public function testNoNodeGrantsOrAccessProcessor() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');

    $this->assertArrayNotHasKey('node_grants', $index['field_settings']);
    $this->assertArrayNotHasKey('content_access', $index['processor_settings']);
  }

  /**
   * Records are keyed on UUID and indexed via cron rather than on save.
   */
  public function testIndexOptions() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');

    $this->assertSame('uuid', $index['options']['object_id_field']);
    $this->assertSame('1', $index['options']['algolia_index_batch_deletion']);
    $this->assertFalse(
      $index['options']['index_directly'],
      'Indexing on save would put an Algolia HTTP call inside every node save.'
    );
    $this->assertFalse(
      $index['read_only'],
      'A read-only index cannot be populated with drush search-api:index.'
    );
    $this->assertSame('hs_algolia', $index['server']);
  }

  /**
   * Algolia handles tokenizing and stemming, so Search API must not.
   */
  public function testTextAnalysisProcessorsAreAbsent() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia');

    foreach (['stemmer', 'stopwords', 'tokenizer', 'transliteration', 'ignorecase', 'highlight'] as $processor) {
      $this->assertArrayNotHasKey(
        $processor,
        $index['processor_settings'],
        "The $processor processor would mutate stored values and break facets."
      );
    }
  }

  /**
   * The per-site keys are the only configuration a site may diverge on.
   */
  public function testConfigIgnorePatterns() {
    $config_ignore = $this->loadConfig('config/default', 'config_ignore.settings');

    foreach ([
      'search_api.server.hs_algolia:status',
      'search_api.index.hs_algolia:status',
      'search_api.index.hs_algolia:options.algolia_index_name',
    ] as $pattern) {
      $this->assertContains($pattern, $config_ignore['ignored_config_entities']);
    }
  }

  /**
   * Both modules are enabled platform wide.
   */
  public function testModulesAreEnabled() {
    $extension = $this->loadConfig('config/default', 'core.extension');

    $this->assertArrayHasKey('hs_algolia', $extension['module']);
    $this->assertArrayHasKey('search_api_algolia', $extension['module']);
  }

}
