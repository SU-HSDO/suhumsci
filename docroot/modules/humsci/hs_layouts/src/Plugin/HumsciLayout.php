<?php

namespace Drupal\hs_layouts\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class HumsciLayout
 *
 * @package Drupal\su_humsci_theme\Plugin
 */
class HumsciLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'section_width' => '',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['section_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Section Width'),
      '#description' => $this->t('Choose if the sections should be full width or limited.'),
      '#empty_option' => $this->t('Full Width'),
      '#default_value' => $this->configuration['section_width'] ?: NULL,
      '#options' => [
        'decanter-grid' => $this->t('Limited Width'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // In case classes is missing entirely, use the defaults.
    $defaults = $this->defaultConfiguration();
    $this->configuration['section_width'] = $form_state->getValue('section_width', $defaults['section_width']);
  }
}