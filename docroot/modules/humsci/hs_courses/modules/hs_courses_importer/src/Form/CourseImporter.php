<?php

namespace Drupal\hs_courses_importer\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\GuzzleException;

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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $guzzle) {
    parent::__construct($config_factory);
    $this->guzzle = $guzzle;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
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
      '#description' => $this->t('One url per line'),
      '#required' => TRUE,
      '#default_value' => $config->get('urls') ? implode("\n", $config->get('urls')) : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $urls = trim($form_state->getValue('urls'));

    foreach (explode("\n", $urls) as $url) {
      $this->validateIsUrl($url, $form, $form_state);
      if (!$form_state->getErrors()) {
        $this->validateIsExploreCourses($url, $form, $form_state);
      }
      if (!$form_state->getErrors()) {
        $this->validateIsXmlUrl($url, $form, $form_state);
      }
    }
  }

  /**
   * Check if the string is a full URL.
   *
   * @param string $url
   *   Url string to check.
   * @param array $form
   *   Original form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function validateIsUrl($url, array &$form, FormStateInterface $form_state) {
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['scheme']) || !isset($parsed_url['host']) || !isset($parsed_url['path']) || !isset($parsed_url['query'])) {
      $form_state->setError($form['urls'], $this->t('Invalid URL Format url: %url', ['%url' => $url]));
    }
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
   */
  protected function validateIsXmlUrl($url, array &$form, FormStateInterface $form_state) {
    try {
      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $this->guzzle->request('GET', $url);
    }
    catch (GuzzleException $e) {
      $form_state->setError($form['urls'], $this->t('Unable to gather data from %url.', ['%url' => $url]));
    }

    $content_type = $response->getHeader('Content-Type');
    foreach ($content_type as $type) {
      if (strpos($type, 'xml') === FALSE) {
        $form_state->setError($form['urls'], $this->t('URL Must be an XML feed. %url', ['%url' => $url]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    global $base_url;

    $this->config('hs_courses_importer.importer_settings')
      ->set('urls', explode("\n", $form_state->getValue('urls')))
      ->set('base_url', $base_url)
      ->save();

    // Clear migration discovery cache after saving.
    Cache::invalidateTags(['migration_plugins']);
  }

}
