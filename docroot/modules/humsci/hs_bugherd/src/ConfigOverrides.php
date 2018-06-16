<?php

namespace Drupal\hs_bugherd;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\encrypt\EncryptService;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Example configuration override.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EncryptService $encrypt_service, LoggerChannelFactoryInterface $logger_factory) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->encryption = $encrypt_service;
    $this->logger = $logger_factory->get('hs_bugerd');
  }

  /**
   * {@inheritdoc}
   *
   * Override the jira rest to decrypt the jira password. This will be removed
   * when https://www.drupal.org/project/jira_rest/issues/2888655 is resolved.
   * This also changes the path of the real_aes key for Acquia environments to
   * point to the file on the Acquia servers.
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('jira_rest.settings', $names)) {

      $original_config = $this->configFactory->getEditable('jira_rest.settings');

      // Get the original encrypted password without overrides.
      $encrypted_password = $original_config->getOriginal('jira_rest.password', FALSE);

      // Get the encrypt profile id.
      $profile_id = $original_config->getOriginal('jira_rest.encryption_id', FALSE);

      if ($profile_id && $encryption_profile = EncryptionProfile::load($profile_id)) {
        try {
          // Decrypt the password.
          $decrypted_password = $this->encryption->decrypt($encrypted_password, $encryption_profile);
          $overrides['jira_rest.settings'] = ['jira_rest' => ['password' => $decrypted_password]];
        }
        catch (\Exception $e) {
          $this->logger->error('Unable to decrypt Jira password: @message', ['@message' => $e->getMessage()]);
        }
      }
    }

    // Override the path of the key for real_aes entity.
    if (in_array('key.key.real_aes', $names) && isset($_ENV['AH_SITE_ENVIRONMENT'])) {
      $overrides['key.key.real_aes'] = [
        'key_provider_settings' => [
          'file_location' => "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/nobackup/apikeys/REAL_AES",
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