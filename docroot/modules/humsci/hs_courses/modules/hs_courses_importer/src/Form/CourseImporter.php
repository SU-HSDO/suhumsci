<?php

namespace Drupal\hs_courses_importer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CourseImporter.
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
      '#default_value' => $config->get('urls') ? implode("\n", $config->get('url')) : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $urls = $form_state->getValue('urls');

    foreach (explode("\n", $urls) as $url) {
      $parsed_url = parse_url($url);
      if (!isset($parsed_url['scheme']) || !isset($parsed_url['host']) || !isset($parsed_url['path']) || !isset($parsed_url['query'])) {
        $form_state->setError($form['url'], $this->t('Invalid URL Format'));
        return;
      }

      if ($parsed_url['host'] != 'explorecourses.stanford.edu') {
        $form_state->setError($form['url'], $this->t('URL Must be for explorecourses.stanford.edu'));
      }

      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $this->guzzle->request('GET', $url);
      $content_type = $response->getHeader('Content-Type');
      foreach ($content_type as $type) {
        if (strpos($type, 'xml') === FALSE) {
          $form_state->setError($form['url'], $this->t('URL Must be an XML feed.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('hs_courses_importer.importer_settings')
      ->set('urls', explode("\n", $form_state->getValue('urls')))
      ->save();
  }

}
