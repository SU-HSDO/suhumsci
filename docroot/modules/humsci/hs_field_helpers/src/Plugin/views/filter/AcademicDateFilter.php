<?php

namespace Drupal\hs_field_helpers\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Class AcademicDateFilter
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("academic_datetime")
 */
class AcademicDateFilter extends Date {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value']['contains']['exception']['default'] = 0;
    $options['value']['contains']['exception_start']['default'] = NULL;
    $options['value']['contains']['exception_end']['default'] = NULL;
    $options['value']['contains']['exception_value']['default'] = '';
    $options['value']['contains']['exception_min']['default'] = '';
    $options['value']['contains']['exception_max']['default'] = '';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if ($form_state->get('exposed')) {
      return;
    }

    $form['value']['exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Exception Time Frame'),
      '#default_value' => $this->value['exception'] ?? 0,
    ];

    $form['value']['exception_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Exception'),
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $this->value['exception_start'] ?? NULL,
    ];

    $form['value']['exception_end'] = [
      '#type' => 'date',
      '#title' => $this->t('End Exception'),
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $this->value['exception_end'] ?? NULL,
    ];

    $form['value']['exception_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exception Value'),
      '#size' => 30,
      '#states' => $form['value']['value']['#states'],
      '#default_value' => $this->value['exception_value'] ?? '',
    ];

    $form['value']['exception_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min'),
      '#size' => 30,
      '#states' => $form['value']['min']['#states'],
      '#default_value' => $this->value['exception_min'] ?? '',
    ];

    $form['value']['exception_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max'),
      '#size' => 30,
      '#states' => $form['value']['max']['#states'],
      '#default_value' => $this->value['exception_max'] ?? '',
    ];

//        dpm($form);
  }

}
