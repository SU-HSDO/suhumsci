<?php

namespace Drupal\hs_field_helpers\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;

/**
 * Class AcademicDateFilter.
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
    $options['value']['contains']['exception_start_month']['default'] = NULL;
    $options['value']['contains']['exception_start_day']['default'] = NULL;
    $options['value']['contains']['exception_end_month']['default'] = NULL;
    $options['value']['contains']['exception_end_day']['default'] = NULL;
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

    $months = cal_info(0);
    $days = range(0, 31);
    unset($days[0]);

    $form['value']['exception_start_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Exception Month'),
      '#default_value' => $this->value['exception_start_month'] ?? NULL,
      '#options' => $months['months'],
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['value']['exception_start_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Exception Day'),
      '#default_value' => $this->value['exception_start_day'] ?? NULL,
      '#options' => $days,
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['value']['exception_end_month'] = [
      '#type' => 'select',
      '#title' => $this->t('End Exception Month'),
      '#options' => $months['months'],
      '#default_value' => $this->value['exception_end_month'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['value']['exception_end_day'] = [
      '#type' => 'select',
      '#title' => $this->t('End Exception Day'),
      '#options' => $days,
      '#default_value' => $this->value['exception_end_day'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="options[value][exception]"]' => ['checked' => TRUE],
        ],
      ],
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
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if ($this->value['exception'] && $this->inException()) {
      $this->value['min'] = $this->value['exception_min'];
      $this->value['max'] = $this->value['exception_max'];
    }
    parent::opBetween($field);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    if ($this->value['exception'] && $this->inException()) {
      $this->value['value'] = $this->value['exception_value'];
    }
    parent::opSimple($field);
  }

  /**
   * Check if current date is in the exception window.
   *
   * @return bool
   *   True if currently in window.
   */
  protected function inException() {
    $this_year = date('Y');
    $end_year = $this_year;

    // Add one year if the start and end dates span over January 1st.
    if ($this->value['exception_start_month'] > $this->value['exception_end_month']) {
      $end_year++;
    }
    elseif ($this->value['exception_start_month'] == $this->value['exception_end_month'] && $this->value['exception_start_day'] > $this->value['exception_end_day']) {
      $end_year++;
    }

    try {
      $start_exception = "$this_year-{$this->value['exception_start_month']}-{$this->value['exception_start_day']}";
      $end_exception = "$end_year-{$this->value['exception_end_month']}-{$this->value['exception_end_day']}";

      $timezone = $this->getTimezone();
      $start = new DateTimePlus($start_exception, new \DateTimeZone($timezone));
      $end = new DateTimePlus($end_exception, new \DateTimeZone($timezone));

      // Add 1 day to the end date since the timestamp will be midnight of
      // that day. This will include the end date in the exception.
      $end->add(new \DateInterval('P2D'));
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $now = time();
    return $now >= $start->getTimestamp() && $now <= $end->getTimestamp();
  }

}
