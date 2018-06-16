<?php

namespace Drupal\hs_bugherd\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptService;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Exception\KeyValueNotSetException;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProvider\ConfigKeyProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a key provider that allows a key to be stored in configuration.
 *
 * @KeyProvider(
 *   id = "encrypted_config",
 *   label = @Translation("Encrypted Configuration"),
 *   description = @Translation("The Configuration key provider stores the key
 *   in Drupal's configuration system as encrypted."), storage_method =
 *   "config", key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class EncryptedConfigKeyProvider extends ConfigKeyProvider {

  /**
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EncryptService $encryption) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'encryption_profile' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $encryption_profiles = [];
    /** @var EncryptionProfile $profile */
    foreach (EncryptionProfile::loadMultiple() as $profile) {
      $encryption_profiles[$profile->id()] = $profile->label();
    }
    // Add an option to indicate that the value is stored Base64-encoded.
    $form['encryption_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption Profile'),
      '#description' => $this->t('Choose which encryption profile to use.'),
      '#default_value' => $this->getConfiguration()['encryption_profile'],
      '#options' => $encryption_profiles,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $key_value = parent::getKeyValue($key);
    $encryption_profile = EncryptionProfile::load($this->configuration['encryption_profile']);
    $key_value = $this->encryption->decrypt($key_value, $encryption_profile);
    return $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $encryption_profile = EncryptionProfile::load($this->configuration['encryption_profile']);
    $this->configuration['key_value'] = $this->encryption->encrypt($key_value, $encryption_profile);

    if (isset($this->configuration['key_value'])) {
      return TRUE;
    }
    else {
      throw new KeyValueNotSetException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    // Key values are not obscured when this provider is used.
    return $key_value;
  }

  public function calculateDependencies() {
    /** @var EncryptionProfile $encryption_profile */
    $encryption_profile = EncryptionProfile::load($this->configuration['encryption_profile']);
    return [
      'config' => [$encryption_profile->getConfigDependencyName()],
    ];
  }

}
