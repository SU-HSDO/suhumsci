<?php

namespace Drupal\hs_megamenu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HSMegaMenuSettings.
 *
 * @package Drupal\hs_megamenu\Form
 *
 * @Form
 */
class HSMegaMenuSettings extends ConfigFormBase {

  /**
   * Instantiates the cache variable.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */

  protected $cache;

  /**
   * Constructor for dependency injection.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache interface.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('cache.render')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_megamenu_form';
  }

  /**
   * Settings configuration form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form array to render.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('hs_megamenu.settings');

    $form['use_hs_megamenu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Stanford HumSci Mega Menu'),
      '#description' => $this->t('Will replace the original main menu with newer mega menu'),
      '#default_value' => $config->get('hs_megamenu.use_hs_megamenu'),
    ];

    return $form;
  }

  /**
   * Submit form action.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hs_megamenu.settings');

    $config->set('hs_megamenu.use_hs_megamenu', $form_state->getValue('use_hs_megamenu'));

    $config->save();
    $this->cache->invalidateAll();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hs_megamenu.settings',
    ];
  }

}