<?php

namespace Drupal\su_humsci_profile\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\encrypt\EncryptService;

/**
 * Example configuration override.
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
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory Service.
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   *   Encryption Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EncryptService $encrypt_service) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->encryption = $encrypt_service;
  }

  /**
   * {@inheritdoc}
   *
   * Override the path to the key for the encryption profile.
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
    return $overrides;
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
