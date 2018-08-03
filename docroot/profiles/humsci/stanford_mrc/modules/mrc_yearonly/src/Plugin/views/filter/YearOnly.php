<?php

namespace Drupal\mrc_yearonly\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\ViewExecutable;

/**
 * Filters by academic year.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("yearonly")
 */
class YearOnly extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->operator = 'in';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['academic']['default'] = FALSE;
    $options['option_sort']['default'] = 'desc';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['academic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Academic Year options'),
      '#default_value' => $this->options['academic'],
    ];
    $form['option_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Options Sort'),
      '#default_value' => $this->options['option_sort'],
      '#options' => [
        'desc' => $this->t('Descending'),
        'asc' => $this->t('Ascending'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function showExposeForm(&$form, FormStateInterface $form_state) {
    parent::showExposeForm($form, $form_state);
    $this->valueForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isExposed()) {
      return $this->t('exposed');
    }
    return parent::adminSummary();
  }

  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => 'value',
      '#options' => $this->getYears(),
      '#multiple' => $this->options['expose']['multiple'],
    ];
  }

  /**
   * @return array
   */
  protected function getYears() {
    $query = \Drupal::database()
      ->select($this->table, 't')
      ->fields('t', [$this->realField])
      ->distinct()
      ->orderBy($this->realField, $this->options['option_sort'])
      ->execute();
    $years = $query->fetchAllKeyed(0, 0);
    if ($this->options['academic']) {
      foreach ($years as &$year) {
        $year = ($year - 1) . " - $year";
      }
    }
    return $years;
  }

}
