<?php

namespace Drupal\hs_algolia;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hs_algolia\Form\SettingsForm;
use Drupal\hs_algolia\Overrides\ConfigOverrides;

/**
 * Decides whether the Algolia search interface replaces the Drupal search.
 *
 * Every place that swaps the Drupal search for Algolia asks this one service,
 * so a site that is half configured always falls back to the Drupal search
 * rather than showing a search page that cannot return results.
 */
class SearchStatus {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Whether visitors should search through Algolia.
   *
   * @return bool
   *   TRUE when the interface is switched on, every value the browser needs
   *   is present, and the server and index are enabled.
   */
  public function isActive(): bool {
    $settings = $this->configFactory->get(ConfigOverrides::SETTINGS);
    if (!$settings->get('search_enabled')) {
      return FALSE;
    }

    foreach (['search_api_key', 'application_id', 'index_name'] as $name) {
      if (trim((string) $settings->get($name)) === '') {
        return FALSE;
      }
    }

    $server = $this->entityTypeManager->getStorage('search_api_server')->load(SettingsForm::SERVER_ID);
    $index = $this->entityTypeManager->getStorage('search_api_index')->load(SettingsForm::INDEX_ID);
    return $server?->status() && $index?->status();
  }

  /**
   * Values the browser needs to query the site's Algolia index.
   *
   * Only the search-only key leaves the server. The write key is never part
   * of this array.
   *
   * @return array
   *   Keys appId, searchKey and indexName.
   */
  public function getClientSettings(): array {
    $settings = $this->configFactory->get(ConfigOverrides::SETTINGS);
    return [
      'appId' => trim((string) $settings->get('application_id')),
      'searchKey' => trim((string) $settings->get('search_api_key')),
      'indexName' => trim((string) $settings->get('index_name')),
    ];
  }

  /**
   * Cache metadata for anything that varies on isActive().
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   Tags for the settings and the server and index entities.
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return (new CacheableMetadata())->addCacheTags([
      'config:' . ConfigOverrides::SETTINGS,
      'config:' . ConfigOverrides::SERVER,
      'config:' . ConfigOverrides::INDEX,
    ]);
  }

}
