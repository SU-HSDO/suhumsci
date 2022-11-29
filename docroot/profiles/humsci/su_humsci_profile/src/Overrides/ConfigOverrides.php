<?php

namespace Drupal\su_humsci_profile\Overrides;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\State\StateInterface;
use Drupal\encrypt\EncryptService;

/**
 * Humsci config overrides on the whole platform.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Config Factory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Encryption Service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config Pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPages;

  /**
   * Drupal state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Current multisite directory path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory Service.
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   *   Encryption Service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type manager service.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages
   *   Config Pages loader service.
   * @param \Drupal\Core\State\StateInterface|null $state
   *   Drupal state service.
   * @param string|null $site_path
   *   Current multisite directory path.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EncryptService $encrypt_service, EntityTypeManagerInterface $entity_type_manager, ConfigPagesLoaderServiceInterface $config_pages, StateInterface $state = NULL, $site_path = NULL) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->encryption = $encrypt_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->configPages = $config_pages;
    $this->state = $state;
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $this->setStageFileProxy($names, $overrides);

    $this->setPeopleOverrides($names, $overrides);
    $this->setNewsOverrides($names, $overrides);
    $this->setCoursesOverrides($names, $overrides);
    $this->setEventOverrides($names, $overrides);
    $this->setPublicationOverrides($names, $overrides);
    $this->setThemeSettingsOverrides($names, $overrides);
    $this->setSearchApiOverrides($names, $overrides);
    $this->setPageCacheQueryIgnore($names, $overrides);

    if (in_array('google_analytics.settings', $names)) {
      if ($value = $this->configPages->getValue('hs_site_options', 'field_site_ga_account')) {
        $overrides['google_analytics.settings']['account'] = $value[0]['value'] ?? '';
      }
    }
    if (in_array('role_watchdog.settings', $names)) {
      $roles = $this->entityTypeManager->getStorage('user_role')
        ->loadMultiple();
      $overrides['role_watchdog.settings']['role_watchdog_monitor_roles'] = array_combine(array_keys($roles), array_keys($roles));
    }
    return $overrides;
  }

  /**
   * Set the config overrides for the people content type.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setPeopleOverrides(array $names, array &$overrides) {
    if (in_array('field.field.node.hs_person.field_hs_person_image', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_person.field_hs_person_image', 'field_people_image');
    }

    if (in_array('field.field.node.hs_person.field_hs_person_square_img', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_person.field_hs_person_square_img', 'field_people_image');
    }
  }

  /**
   * Set up the stage file proxy settings based on the urls in state.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setStageFileProxy(array $names, array &$overrides) {
    if (in_array('stage_file_proxy.settings', $names) && $this->state) {
      $site_dir = str_replace('sites/', '', $this->sitePath);

      if ($base_url = $this->state->get('xmlsitemap_base_url')) {
        $overrides['stage_file_proxy.settings'] = [
          'origin' => $base_url,
          'origin_dir' => "sites/$site_dir/files",
        ];
      }
    }
  }

  /**
   * Add all the themes' settings configs to be ignored.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setThemeSettingsOverrides(array $names, array &$overrides) {
    if (in_array('config_ignore.settings', $names)) {
      $themes = $this->configFactory->getEditable('core.extension')
        ->getOriginal('theme');
      $ignored = $this->configFactory->getEditable('config_ignore.settings')
        ->getOriginal('ignored_config_entities');

      // Add all of the enabled themes' settings configs to be ignored.
      foreach (array_keys($themes) as $theme) {
        $ignored[] = "$theme.settings";
      }
      $overrides['config_ignore.settings']['ignored_config_entities'] = $ignored;

      if (InstallerKernel::installationAttempted()) {
        foreach ($overrides['config_ignore.settings']['ignored_config_entities'] as &$item) {
          $item = 'foo';
        }
      }
    }
  }

  /**
   * Set any configuration overrides for things related to news content type.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setNewsOverrides(array $names, array &$overrides) {
    if (in_array('field.field.node.hs_news.field_hs_news_image', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_news.field_hs_news_image', 'field_news_image');
    }

    if (in_array('rabbit_hole.behavior_settings.node_type_hs_news', $names)) {
      $disabled = $this->configPages->getValue('hs_site_options', 'field_site_news_rabbit');
      if (!empty($disabled[0]['value'])) {
        $overrides['rabbit_hole.behavior_settings.node_type_hs_news'] = [
          'action' => 'display_page',
          'allow_override' => 0,
          'redirect' => '',
        ];
      }
    }
  }

  /**
   * Set any configuration overrides for things related to course content type.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setCoursesOverrides(array $names, array &$overrides) {
    if (in_array('rabbit_hole.behavior_settings.node_type_hs_course', $names)) {
      $disabled = $this->configPages->getValue('hs_site_options', 'field_site_courses_rabbit');
      if (!empty($disabled[0]['value'])) {
        $overrides['rabbit_hole.behavior_settings.node_type_hs_course'] = [
          'action' => 'display_page',
          'allow_override' => 0,
          'redirect' => '',
        ];
      }
    }
  }

  /**
   * Set any configuration overrides for things related to events content type.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setEventOverrides(array $names, array &$overrides) {
    if (in_array('field.field.node.hs_event.field_hs_event_image', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_event.field_hs_event_image', 'field_events_image');
    }
    if (in_array('migrate_plus.migration.hs_localist_individual', $names)) {
      $api_urls = $this->getUrlsFromLinkField('localist_events', 'field_url_individ');
      $bookmark_urls = $this->getUrlsFromLinkField('localist_events', 'field_url_book_i');
      $source_urls = array_merge($api_urls, $bookmark_urls);
      $overrides['migrate_plus.migration.hs_localist_individual']['source']['urls'] = $source_urls;
      $overrides['migrate_plus.migration.hs_localist_individual']['status'] = !empty($source_urls);
    }
    if (in_array('migrate_plus.migration.hs_localist_scheduled', $names)) {
      $api_urls = $this->getUrlsFromLinkField('localist_events', 'field_url_separate');
      $bookmark_urls = $this->getUrlsFromLinkField('localist_events', 'field_url_book_s');
      $source_urls = array_merge($api_urls, $bookmark_urls);
      $overrides['migrate_plus.migration.hs_localist_scheduled']['source']['urls'] = $source_urls;
      $overrides['migrate_plus.migration.hs_localist_scheduled']['status'] = !empty($source_urls);
    }
  }

  /**
   * Get an array of urls from the config page link field.
   *
   * @param string $config_page
   *   Config page ID.
   * @param string $field_name
   *   Field ID.
   *
   * @return string[]
   *   List of values form the uri column.
   */
  protected function getUrlsFromLinkField(string $config_page, string $field_name): array {
    /** @var \Drupal\config_pages\ConfigPagesInterface $config_page */
    $config_page = $this->entityTypeManager->getStorage('config_pages')
      ->load($config_page);
    $urls = [];
    if (
      $config_page &&
      $config_page->hasField($field_name) &&
      $config_page->get($field_name)->count()
    ) {
      foreach ($config_page->get($field_name)->getValue() as $value) {
        $urls[] = $value['uri'];
      }
    }
    return $urls;
  }

  /**
   * Set any configuration overrides for things related to publication content.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setPublicationOverrides(array $names, array &$overrides) {
    if (in_array('field.field.node.hs_publications.field_hs_publication_image', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_publications.field_hs_publication_image', 'field_publication_image');
    }
  }

  /**
   * Set the config overrides for media fields from config pages values.
   *
   * @param array $overrides
   *   Config overrides.
   * @param string $config_name
   *   Config entity name.
   * @param string $config_pages_field
   *   Field on the config pages to grab the value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setMediaFieldOverrides(array &$overrides, $config_name, $config_pages_field) {
    $media_field_value = $this->configPages->getValue('field_default_values', $config_pages_field);

    if (empty($media_field_value[0]['target_id'])) {
      return;
    }

    $media_entity = $this->entityTypeManager->getStorage('media')
      ->load($media_field_value[0]['target_id']);
    if (!$media_entity) {
      return;
    }

    $overrides[$config_name]['default_value'][0] = [
      'weight' => 0,
      'target_uuid' => $media_entity->uuid(),
    ];

  }

  /**
   * Add all nodes to the search api indexing.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setSearchApiOverrides(array $names, array &$overrides) {
    if (!in_array('search_api.index.default_index', $names)) {
      return;
    }
    $node_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();
    foreach (array_keys($node_types) as $node_type) {
      $overrides['search_api.index.default_index']['field_settings']['rendered']['configuration']['view_mode']['entity:node'][$node_type] = 'search_index';
    }
  }

  /**
   * Set the config overrides for the page_cache_query_ignore settings.
   *
   * @param array $names
   *   Array of config names.
   * @param array $overrides
   *   Keyed array of config overrides.
   */
  protected function setPageCacheQueryIgnore(array $names, array &$overrides) {
    if (!in_array('page_cache_query_ignore.settings', $names)) {
      return;
    }
    $original_setting = $this->configFactory->getEditable('page_cache_query_ignore.settings')
      ->getOriginal('query_parameters', FALSE);
    $allowed_parameters = [
      'hash',
      'offset',
      'page',
      'search',
      'sort_by',
      'sort_order',
      'url',
    ];
    $view_params = $this->getViewQueryParams();
    $params = [
      ...$original_setting,
      ...$allowed_parameters,
      ...$view_params,
    ];
    asort($params);
    $overrides['page_cache_query_ignore.settings']['query_parameters'] = array_values(array_unique($params));
  }

  /**
   * Get all the query parameters for exposed filters in all views.
   *
   * @return array
   *   Associative array of query keys.
   */
  public function getViewQueryParams(): array {
    $queries = [];
    /** @var \Drupal\views\Entity\View[] $views */
    $views = $this->entityTypeManager->getStorage('view')
      ->loadByProperties(['status' => TRUE]);
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $filters = $display['display_options']['filters'] ?? [];
        foreach ($filters as $filter) {
          $queries[] = $filter['expose']['identifier'] ?? NULL;
        }
      }
    }
    $queries = array_unique(array_filter($queries));
    asort($queries);
    return array_values($queries);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
