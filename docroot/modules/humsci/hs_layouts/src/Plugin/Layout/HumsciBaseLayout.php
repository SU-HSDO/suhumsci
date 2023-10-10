<?php

namespace Drupal\hs_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class HumsciBaseLayout.
 *
 * @package Drupal\hs_layouts\Plugin
 */
class HumsciBaseLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config += [
      'main_content' => 'none',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['main_content'] = [
      '#type' => 'select',
      '#title' => $this->t('Main Content begins at'),
      '#description' => $this->t('Where does the page’s main content begin? Typically this is the Main Region but occasionally is the Left Sidebar. Choose “None” if you’ve already set this value on another section.'),
      '#default_value' => $this->configuration['main_content'] ?: 'none',
      '#options' => [
        'none' => $this->t('None'),
        'main-region' => $this->t('Main Region'),
        'left-sidebar' => $this->t('Left Sidebar'),
      ]
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
    parent::submitConfigurationForm($form, $form_state);
    $defaults = $this->defaultConfiguration();
    $this->configuration['main_content'] = $form_state->getValue('main_content', $defaults['main_content']);
  }

}

