<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Entity\Key;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * Class BugherdConnectionSettingsForm.
 */
class BugherdConnectionSettingsForm extends ConfigFormBase {

  /**
   * Drupal\encrypt\EncryptServiceInterface definition.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryption;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptServiceInterface $encryption) {
    parent::__construct($config_factory);
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_bugherd.connection_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bugherd_connection_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('hs_bugherd.connection_settings');

    $keys = [];
    /** @var \Drupal\key\Entity\Key $key */
    foreach (Key::loadMultiple() as $key) {
      $keys[$key->id()] = $key->label();
    }

    $form['api_key'] = [
      '#type' => 'select',
      '#title' => $this->t('BugHerd API key'),
      '#default_value' => $config->get('api_key'),
      '#options' => $keys,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('hs_bugherd.connection_settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
  }

}
