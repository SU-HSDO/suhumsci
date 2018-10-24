<?php

namespace Drupal\hs_capx\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hs_capx\Capx;
use Drupal\key\Entity\Key;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class to set the CAPx user credentials.
 *
 * @package Drupal\hs_capx\Form
 */
class CapxCredsForm extends ConfigFormBase {

  /**
   * CAPx Service.
   *
   * @var \Drupal\hs_capx\Capx
   */
  protected $capx;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('capx')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Capx $capx) {
    parent::__construct($config_factory);
    $this->capx = $capx;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'capx_creds_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_capx.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('hs_capx.settings');
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $config->get('username'),
    ];

    $keys = Key::loadMultiple();

    // Loop through the various keys and using reference, change the key object
    // into the key's label for use in the select form element.
    foreach ($keys as &$key) {
      $key = $key->label();
    }

    $key_description = $this->t('Choose an available key. If the desired key is not listed, <a href=":link">create a new key</a>.', [
      ':link' => Url::fromRoute('entity.key.add_form')
        ->toString(),
    ]);
    $form['password'] = [
      '#type' => 'select',
      '#title' => $this->t('Password'),
      '#description' => $key_description,
      '#required' => TRUE,
      '#options' => $keys,
      '#default_value' => $config->get('password'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check the given username and password will authenticate with CAPx.
    $this->capx->setUsername($form_state->getValue('username'));
    $password_key = Key::load($form_state->getValue('password'));
    $this->capx->setPassword($password_key->getKeyValue());

    Cache::invalidateTags(['capx']);

    if (!$this->capx->testConnection()) {
      $form_state->setError($form['username'], $this->t('Invalid Credentials'));
      $form_state->setError($form['password'], $this->t('Invalid Credentials'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable('hs_capx.settings')
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();

    $this->capx->setUsername($form_state->getValue('username'));
    $password_key = Key::load($form_state->getValue('password'));
    $this->capx->setPassword($password_key->getKeyValue());

    // We have valid username and passwords, lets get the organization data.
    $this->capx->syncOrganizations();
  }

}
