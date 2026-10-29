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
   * Every shipped Algolia server, keyed to the index it serves.
   */
  const SERVER_INDEXES = [
    'hs_algolia' => 'hs_algolia',
    'hs_algolia_federated' => 'hs_algolia_trainings',
  ];

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
    foreach ([
      'search_api.server.hs_algolia',
      'search_api.index.hs_algolia',
      'hs_algolia.settings',
      'search_api.server.hs_algolia_federated',
      'search_api.index.hs_algolia_trainings',
    ] as $name) {
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
    foreach (self::SERVER_INDEXES as $server => $index) {
      foreach (['search_api.server.' . $server, 'search_api.index.' . $index] as $name) {
        $config = $this->loadConfig('config/default', $name);
        $this->assertFalse($config['status'], "$name must ship disabled.");
      }
    }
  }

  /**
   * The server ships without credentials, which stay out of this repository.
   */
  public function testServerShipsWithoutCredentials() {
    foreach (array_keys(self::SERVER_INDEXES) as $id) {
      $server = $this->loadConfig('config/default', 'search_api.server.' . $id);

      $this->assertSame('search_api_algolia', $server['backend']);
      $this->assertSame('', $server['backend_config']['application_id'], "$id must ship without an application ID.");
      $this->assertSame('', $server['backend_config']['api_key'], "$id must ship without an API key.");
    }
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
   * The Training Hub index carries Training content and nothing else.
   *
   * Every site writes into one shared index, so a wider allow-list would
   * publish other content types from every participating site at once.
   */
  public function testTrainingIndexOnlyIndexesTraining() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia_trainings');
    $bundles = $index['datasource_settings']['entity:node']['bundles'];

    $this->assertFalse($bundles['default']);
    $this->assertSame(['hs_training'], $bundles['selected']);
    $this->assertSame(['entity:node'], array_keys($index['datasource_settings']));
    $this->assertSame(
      ['hs_training' => 'search_index'],
      $index['field_settings']['html']['configuration']['view_mode']['entity:node']
    );
  }

  /**
   * A site can never delete another site's Training Hub records.
   *
   * A search on the shared index returns records from every site, and any
   * that are not local content fail to load. With delete_on_fail, Search API
   * removes failed items through the backend, and the Algolia backend skips
   * that removal only while records are keyed on a custom object ID field.
   *
   * @see \Drupal\search_api\Entity\Index::loadItemsMultiple()
   * @see \Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend::deleteItems()
   */
  public function testTrainingIndexCannotDeleteOtherSitesRecords() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia_trainings');

    $this->assertSame('uuid', $index['options']['object_id_field']);
    $this->assertFalse($index['options']['delete_on_fail']);
  }

  /**
   * Training contact names are never sent to the Training Hub.
   *
   * Contacts are excluded by decision, because every record is readable from
   * the browser with the public search key. The field must stay out of both
   * the indexed fields and the display the rendered HTML is built from.
   */
  public function testTrainingContactsAreNotIndexed() {
    $index = $this->loadConfig('config/default', 'search_api.index.hs_algolia_trainings');
    foreach ($index['field_settings'] as $name => $field) {
      $this->assertStringNotContainsString('field_hs_training_contacts', $field['property_path'] ?? '', "$name indexes training contacts.");
      $this->assertStringNotContainsString('field_hs_training_contacts', $field['configuration']['value'] ?? '', "$name indexes training contacts.");
    }
    $this->assertNotContains('field.storage.node.field_hs_training_contacts', $index['dependencies']['config']);

    $display = $this->loadConfig('config/default', 'core.entity_view_display.node.hs_training.search_index');
    $this->assertArrayNotHasKey('field_hs_training_contacts', $display['content']);
    $this->assertTrue($display['hidden']['field_hs_training_contacts']);
    $this->assertArrayNotHasKey('layout_builder', $display['third_party_settings'] ?? [], 'A layout would render blocks the field list does not control.');
  }

  /**
   * Unpublished content is excluded at index time.
   */
  public function testUnpublishedContentIsExcluded() {
    foreach (self::SERVER_INDEXES as $id) {
      $index = $this->loadConfig('config/default', 'search_api.index.' . $id);

      $this->assertArrayHasKey(
        'entity_status',
        $index['processor_settings'],
        "The entity_status processor keeps unpublished content out of $id."
      );
    }
  }

  /**
   * Content is rendered as an anonymous visitor, never a privileged role.
   */
  public function testRenderedItemUsesAnonymousRole() {
    foreach (self::SERVER_INDEXES as $id) {
      $index = $this->loadConfig('config/default', 'search_api.index.' . $id);

      $this->assertSame(
        ['anonymous'],
        $index['field_settings']['html']['configuration']['roles'],
        "Rendering $id as any other role would push non-public markup into Algolia."
      );
    }
  }

  /**
   * Node grants are never handed to a third party service.
   */
  public function testNoNodeGrantsOrAccessProcessor() {
    foreach (self::SERVER_INDEXES as $id) {
      $index = $this->loadConfig('config/default', 'search_api.index.' . $id);

      $this->assertArrayNotHasKey('node_grants', $index['field_settings']);
      $this->assertArrayNotHasKey('content_access', $index['processor_settings']);
    }
  }

  /**
   * Records are keyed on UUID and indexed via cron rather than on save.
   *
   * UUID keys are what let several sites share the Training Hub index without
   * overwriting each other's records.
   */
  public function testIndexOptions() {
    foreach (self::SERVER_INDEXES as $server => $id) {
      $index = $this->loadConfig('config/default', 'search_api.index.' . $id);

      $this->assertSame('uuid', $index['options']['object_id_field']);
      $this->assertSame('1', $index['options']['algolia_index_batch_deletion']);
      $this->assertSame('', $index['options']['algolia_index_name'], "$id must ship without an Algolia index name.");
      $this->assertFalse(
        $index['options']['index_directly'],
        'Indexing on save would put an Algolia HTTP call inside every node save.'
      );
      $this->assertFalse(
        $index['read_only'],
        'A read-only index cannot be populated with drush search-api:index.'
      );
      $this->assertSame($server, $index['server']);
    }
  }

  /**
   * Algolia handles tokenizing and stemming, so Search API must not.
   */
  public function testTextAnalysisProcessorsAreAbsent() {
    foreach (self::SERVER_INDEXES as $id) {
      $index = $this->loadConfig('config/default', 'search_api.index.' . $id);

      foreach (['stemmer', 'stopwords', 'tokenizer', 'transliteration', 'ignorecase', 'highlight'] as $processor) {
        $this->assertArrayNotHasKey(
          $processor,
          $index['processor_settings'],
          "The $processor processor would mutate stored values and break facets in $id."
        );
      }
    }
  }

  /**
   * Per-site values are ignored on import and everything else is not.
   *
   * Status stays on the entities because enabling has save-time side effects.
   * Credentials and the index name live in hs_algolia.settings and a key
   * entity, so those are ignored whole and the entity keys are not.
   */
  public function testConfigIgnorePatterns() {
    $config_ignore = $this->loadConfig('config/default', 'config_ignore.settings');
    $patterns = $config_ignore['ignored_config_entities'];

    foreach ([
      'search_api.server.hs_algolia:status',
      'search_api.index.hs_algolia:status',
      'search_api.server.hs_algolia_federated:status',
      'search_api.index.hs_algolia_trainings:status',
      'hs_algolia.settings',
      'key.key.*',
    ] as $pattern) {
      $this->assertContains($pattern, $patterns);
    }

    foreach (self::SERVER_INDEXES as $id) {
      $this->assertNotContains("search_api.index.$id:options.algolia_index_name", $patterns, 'The index name is a runtime override, not an ignored key.');
    }

    // The shipped platform keys must still import.
    foreach (['real_aes', 'hs_shield_key'] as $shipped_key) {
      $this->assertContains('~key.key.' . $shipped_key, $patterns);
      $this->assertFileExists($this->repoRoot() . '/config/default/key.key.' . $shipped_key . '.yml');
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
