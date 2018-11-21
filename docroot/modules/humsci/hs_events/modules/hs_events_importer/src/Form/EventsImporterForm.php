<?php

namespace Drupal\hs_events_importer\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventsImporterForm.
 */
class EventsImporterForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'events_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_events_importer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $urls = $this->config('hs_events_importer.settings')->get('urls');
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Urls'),
      '#description' => $this->t('Leave empty to disable importer'),
      '#default_value' => implode("\n", $urls),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $urls = array_filter(explode("\n", str_replace("\r", '', $form_state->getValue('urls'))));
    foreach ($urls as &$url) {
      $url = trim($url);
      $this->validateUrl($url, $form, $form_state);
    }

    $form_state->setValue('urls', $urls);
  }

  /**
   * Validate that the user entered values are xml feeds from stanford events.
   *
   * @param string $url
   *   Url to events.stanford.edu.
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function validateUrl($url, array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($url, TRUE)) {
      $form_state->setError($form['urls'], $this->t('@url is not a valid url.', ['@url' => $url]));
      return;
    }

    $parsed_url = parse_url($url);
    if ($parsed_url['host'] != 'events.stanford.edu') {
      $form_state->setError($form['urls'], $this->t('@url is not an events.stanford.edu url.', ['@url' => $url]));
    }

    if (!isset($parsed_url['path']) || strpos($parsed_url['path'], 'xml') === FALSE) {
      $form_state->setError($form['urls'], $this->t('@url is not an xml url.', ['@url' => $url]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()
      ->getEditable('hs_events_importer.settings')
      ->set('urls', $form_state->getValue('urls'))
      ->save();
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['migration_plugins']);
  }

}
