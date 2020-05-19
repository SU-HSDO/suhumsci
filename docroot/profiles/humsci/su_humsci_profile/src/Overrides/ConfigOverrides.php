<?php

namespace Drupal\su_humsci_profile\Overrides;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EncryptService $encrypt_service, EntityTypeManagerInterface $entity_type_manager, ConfigPagesLoaderServiceInterface $config_pages) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->encryption = $encrypt_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->configPages = $config_pages;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Override the path of the key for real_aes entity on local environments.
    if (in_array('key.key.real_aes', $names) && !isset($_ENV['AH_SITE_ENVIRONMENT'])) {
      $overrides['key.key.real_aes'] = [
        'key_provider_settings' => [
          'file_location' => "../keys/REAL_AES",
        ],
      ];
    }

    if (in_array('field.field.node.hs_person.field_hs_person_image', $names)) {
      $this->setMediaFieldOverrides($overrides, 'field.field.node.hs_person.field_hs_person_image', 'field_people_image');
    }
    $this->setNewsOverrides($names, $overrides);
    $this->setCoursesOverrides($names, $overrides);

    if (in_array('google_analytics.settings', $names)) {
      if ($value = $this->configPages->getValue('hs_site_options', 'field_site_ga_account')) {
        $overrides['google_analytics.settings']['account'] = $value[0]['value'];
      }
    }
    return $overrides;
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
