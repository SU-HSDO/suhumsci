<?php

namespace Drupal\hs_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class HumsciLayout.
 *
 * @package Drupal\su_humsci_theme\Plugin
 */
class HumsciLayout extends HumsciBaseLayout {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config += [
      'section_width' => '',
      'region_widths' => '',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['section_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Section Width'),
      '#description' => $this->t('Choose if the sections should be full width or limited.'),
      '#default_value' => $this->configuration['section_width'] ?: 'hs-full-width',
      '#options' => [
        'hs-full-width' => $this->t('Full Width'),
        'decanter-grid' => $this->t('Limited Width'),
      ],
    ];

    $form['region_widths'] = [
      '#type' => 'select',
      '#title' => $this->t('Region Widths'),
      '#description' => $this->t('change the widths of the 3 columns.'),
      '#default_value' => $this->configuration['region_widths'] ?: 'center',
      '#options' => [
        'center' => $this->t('Larger Center Column'),
        'equal' => $this->t('Equal Columns'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // In case classes is missing entirely, use the defaults.
    $defaults = $this->defaultConfiguration();
    $this->configuration['section_width'] = $form_state->getValue('section_width', $defaults['section_width']);
    $this->configuration['region_widths'] = $form_state->getValue('region_widths', $defaults['region_widths']);
  }

}
