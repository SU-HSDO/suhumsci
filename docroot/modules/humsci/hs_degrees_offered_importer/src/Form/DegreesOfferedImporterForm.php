<?php

namespace Drupal\hs_degrees_offered_importer\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DegreesOfferedImporterForm.
 */
class DegreesOfferedImporterForm extends ConfigFormBase {

  /**
   * URL Endpoint for getting categories.
   */
  const STANFORD_degrees_offered_importer_XML = "";

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $guzzle;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->guzzle = $client;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'degrees_offered_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_degrees_offered_importer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $urls = $this->config('hs_degrees_offered_importer.settings')->get('urls') ?: [];

    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Degrees Offered URLs'),
      '#description' => $this->t('One url per line. Leave empty to disable the importer.'),
      '#default_value' => $config->get('urls') ? implode("\n", $config->get('urls')) : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $urls = explode("\n", trim($form_state->getValue('urls')));

    foreach ($urls as &$url) {
      $url = trim($url);
      if (strpos($url, 'view=xml') === FALSE) {
        $url .= '&view=xml-20140630';
      }
      if (!UrlHelper::isValid($url, TRUE)) {
        $form_state->setError($form['urls'], $this->t('Invalid URL Format url: %url', ['%url' => $url]));
        return;
      }
    }
    $form_state->setValue('urls', implode("\n", $urls));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()
      ->getEditable('hs_degrees_offered_importer.settings')
      ->set('urls', $form_state->getValue('urls'))
      ->save();
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['migration_plugins']);

    // Add permission to execute importer.
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load('site_manager');
    if ($role) {
      $role->grantPermission('import hs_degrees_offered_importer migration');
      $role->save();
    }
  }

}
