<?php

namespace Drupal\hs_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class HumsciImageLayout.
 *
 * @package Drupal\hs_layouts\Plugin
 */
class HumsciImageLayout extends HumsciLayout {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config += [
      'image_float' => 'align-left',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['image_float'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Side'),
      '#description' => $this->t('Float the image to a desired side.'),
      '#default_value' => $this->configuration['image_float'] ?: 'left',
      '#options' => [
        'align-left' => $this->t('Left'),
        'align-right' => $this->t('Right'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $defaults = $this->defaultConfiguration();
    $this->configuration['image_float'] = $form_state->getValue('image_float', $defaults['image_float']);
  }

}
