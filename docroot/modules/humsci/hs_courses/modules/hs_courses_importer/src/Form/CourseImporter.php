<?php

namespace Drupal\hs_courses_importer\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CourseImporter.
 *
 * Settings for the course importer migration entity.
 */
class CourseImporter extends ConfigFormBase {

  /**
   * Guzzle Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $guzzle;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $guzzle, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->guzzle = $guzzle;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hs_courses_importer.importer_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'course_importer';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hs_courses_importer.importer_settings');
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Course Feed URL'),
      '#description' => $this->t('One url per line. Leave empty to disable the importer.'),
      '#default_value' => $config->get('urls') ? implode("\n", $config->get('urls')) : '',
    ];

    return parent::buildForm($form, $form_state);
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

      $this->validateIsExploreCourses($url, $form, $form_state);

      // If there are errors from explore courses validation, don't check if its
      // an XML source.
      if (!$form_state->getErrors()) {
        $this->validateIsXmlUrl($url, $form, $form_state);
      }
    }
    $form_state->setValue('urls', implode("\n", $urls));
  }

  /**
   * Check if the url points to explorecourses.stanford.edu.
   *
   * @param string $url
   *   Url string to check.
   * @param array $form
   *   Original form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function validateIsExploreCourses($url, array &$form, FormStateInterface $form_state) {
    $parsed_url = parse_url($url);
    if ($parsed_url['host'] != 'explorecourses.stanford.edu') {
      $form_state->setError($form['urls'], $this->t('URL %url Must be for explorecourses.stanford.edu', ['%url' => $url]));
    }
  }

  /**
   * Check that the url points to an XML feed.
   *
   * @param string $url
   *   Url string to check.
   * @param array $form
   *   Original form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function validateIsXmlUrl($url, array &$form, FormStateInterface $form_state) {
    /** @var \GuzzleHttp\Psr7\Response $response */
    $response = $this->guzzle->request('GET', $url);
    $content_type = $response->getHeader('Content-Type');
    foreach ($content_type as $type) {
      if (strpos($type, 'xml') !== FALSE) {
        return;
      }
    }
    $form_state->setError($form['urls'], $this->t('URL Must be an XML feed. %url', ['%url' => $url]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    global $base_url;

    $urls = explode("\n", $form_state->getValue('urls'));
    foreach ($urls as &$url) {
      $url = trim($url, " \t\n\r\0\x0B,");
    }
    $this->config('hs_courses_importer.importer_settings')
      ->set('urls', $urls)
      ->set('base_url', $base_url)
      ->save();

    // Clear migration discovery cache after saving.
    Cache::invalidateTags(['migration_plugins']);

    // Add permission to execute importer.
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load('site_manager');
    if ($role) {
      $role->grantPermission('import hs_courses migration');
      $role->save();
    }
  }

}
