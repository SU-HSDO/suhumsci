<?php

namespace Drupal\hs_bugherd;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EncryptService $encrypt_service) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->encryption = $encrypt_service;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('jira_rest.settings', $names) && $this->moduleHandler->moduleExists('encrypt')) {

      // Get the original encrypted password without overrides.
      $encrypted_password = $this->configFactory->getEditable('jira_rest.settings')
        ->getOriginal('jira_rest.password', FALSE);

      if ($encryption_profile = EncryptionProfile::load('real_aes')) {

        try {
          // Decrypt the password.
          $decrypted_password = $this->encryption->decrypt($encrypted_password, $encryption_profile);
          $overrides['jira_rest.settings'] = ['jira_rest' => ['password' => $decrypted_password]];
        }
        catch (\Exception $e) {
          // Nothing to do.
        }
      }
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