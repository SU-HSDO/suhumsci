<?php

namespace Drupal\hs_field_helpers\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;

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
    //    $options['contains']['exception']['default'] = 0;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['exception'] = [
        '#type' => 'details',
        '#title' => $this->t('Exception Time Frame'),
        '#open' => FALSE,
        '#tree' => TRUE,
      ];
      $form['exception']['exception'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add Exception Time Frame'),
        //        '#default_value' => isset($this->value['exception']) ? $this->value['exception'] : 0,
      ];
      $states = [
        'visible' => [
          '::input[name="options[exception][exception]"]' => ['checked' => TRUE],
        ],
      ];

      $form['exception']['exception_start'] = [
        '#type' => 'date',
        '#title' => $this->t('Start Exception'),
        '#states' => $states,
      ];

      $form['exception']['exception_end'] = [
        '#type' => 'date',
        '#title' => $this->t('End Exception'),
        '#states' => $states,
      ];

      $form['exception']['exception_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Exception Value'),
        '#size' => 30,
        '#states' => $states,
      ];

      $form['exception']['exception_min'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Min'),
        '#size' => 30,
        '#states' => $states,
      ];

      $form['exception']['exception_max'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Max'),
        '#size' => 30,
        '#states' => $states,
      ];

      dpm($form);
    }
  }

}
