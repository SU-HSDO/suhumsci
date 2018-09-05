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
  public function hasExtraOptions() {
    return !$this->isExposed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    if ($form_state->get('exposed')) {
      return;
    }

    $form['exception']['exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Exception Time Frame'),
      '#default_value' => $this->options['exception']['exception'] ?? 0,
    ];

    $months = cal_info(0);
    $days = range(0, 31);
    unset($days[0]);

    $form['exception']['start_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Exception Month'),
      '#default_value' => $this->options['exception']['start_month'] ?? NULL,
      '#options' => $months['months'],
      '#prefix' => '<div class="exception-start-wrapper">',
    ];

    $form['exception']['start_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Exception Day'),
      '#default_value' => $this->options['exception']['start_day'] ?? NULL,
      '#options' => $days,
      '#suffix' => '</div>',
    ];

    $form['exception']['end_month'] = [
      '#type' => 'select',
      '#title' => $this->t('End Exception Month'),
      '#options' => $months['months'],
      '#default_value' => $this->options['exception']['end_month'] ?? NULL,
      '#prefix' => '<div class="exception-end-wrapper">',
    ];

    $form['exception']['end_day'] = [
      '#type' => 'select',
      '#title' => $this->t('End Exception Day'),
      '#options' => $days,
      '#default_value' => $this->options['exception']['end_day'] ?? NULL,
      '#suffix' => '</div>',
    ];

    $between_operators = ['between', 'not between'];
    if (!in_array($this->operator, $between_operators)) {
      $form['exception']['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Exception Value'),
        '#size' => 30,
        '#default_value' => $this->options['exception']['value'] ?? '',
      ];
    }
    else {
      $form['exception']['min'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Exception Min'),
        '#size' => 30,
        '#default_value' => $this->options['exception']['min'] ?? '',
      ];
      $form['exception']['max'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Exception Max'),
        '#size' => 30,
        '#default_value' => $this->options['exception']['max'] ?? '',
      ];
    }
    $form['#attached']['library'][] = 'hs_field_helpers/date_exception';
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if (!empty($this->options['exception']) && $this->options['exception']['exception'] && $this->inException()) {
      $this->value['min'] = $this->options['exception']['min'] ?? $this->value['min'];
      $this->value['max'] = $this->options['exception']['max'] ?? $this->value['max'];
    }
    parent::opBetween($field);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    if (!empty($this->options['exception']) && $this->options['exception']['exception'] && $this->inException()) {
      $this->value['value'] = $this->options['exception']['value'];
    }
    parent::opSimple($field);
  }

  /**
   * Check if current date is in the exception window.
   *
   * @return bool
   *   True if currently in window.
   *
   * @throws \Exception
   */
  protected function inException() {
    $this_year = date('Y');
    $end_year = $this_year;

    // Add one year if the start and end dates span over January 1st.
    if ($this->isMultipleYears()) {
      $end_year++;
    }

    $start_exception = "$this_year-{$this->options['exception']['start_month']}-{$this->options['exception']['start_day']}";
    $end_exception = "$end_year-{$this->options['exception']['end_month']}-{$this->options['exception']['end_day']}";

    $timezone = $this->getTimezone();
    $start = new DateTimePlus($start_exception, new \DateTimeZone($timezone));
    $end = new DateTimePlus($end_exception, new \DateTimeZone($timezone));

    if ($start->hasErrors() || $end->hasErrors()) {
      return FALSE;
    }

    // Add 1 day to the end date since the timestamp will be midnight of
    // that day. This will include the end date in the exception.
    $end->add(new \DateInterval('P1D'));
    $now = time();
    return $now >= $start->getTimestamp() && $now <= $end->getTimestamp();
  }

  /**
   * Check if the start and end dates span over the new year.
   *
   * @return bool
   *   True if its over a new year.
   */
  protected function isMultipleYears() {
    if ($this->options['exception']['start_month'] > $this->options['exception']['end_month']) {
      return TRUE;
    }

    if (
      $this->options['exception']['start_month'] == $this->options['exception']['end_month'] &&
      $this->options['exception']['start_day'] > $this->options['exception']['end_day']
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $summary[] = parent::adminSummary();
    if (isset($this->options['exception']['exception']) && $this->options['exception']['exception']) {
      // Change the filter values so we can easily reuse the parent method.
      $this->value['min'] = $this->options['exception']['min'] ?? $this->value['min'];
      $this->value['max'] = $this->options['exception']['max'] ?? $this->value['max'];
      $this->value['value'] = $this->options['exception']['value'] ?? $this->value['value'];

      $summary[] = t('Exception:')->render();
      $summary[] = parent::adminSummary();
    }
    return implode(' ', $summary);
  }

}
