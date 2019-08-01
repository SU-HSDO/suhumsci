<?php

namespace Drupal\hs_page_reports\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageReports to provide 404 and 403 report pages.
 *
 * @package Drupal\hs_page_reports\Controller
 */
class PageReports extends FormBase {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Which 404 or 403 report for the form.
   *
   * @var int
   */
  protected $report;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * PageReports constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   Database connection service.
   */
  public function __construct(Connection $db_connection) {
    $this->database = $db_connection;

    $path = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
    $this->report = $path[2] == 'page-not-found' ? 404 : 403;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'hs_page_reports';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['report_table'] = $this->getTable($this->report);

    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Report'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->delete('hs_page_reports')
      ->condition('code', $this->report)
      ->execute();
  }

  /**
   * Get a table render array.
   *
   * @param int $code
   *   Response code.
   *
   * @return array
   *   Table render array.
   */
  protected function getTable($code) {
    $records = $this->database->select('hs_page_reports', 'h')
      ->fields('h', ['path', 'count'])
      ->condition('code', $code)
      ->orderBy('count', 'DESC')
      ->range(0, 25)
      ->execute()
      ->fetchAllKeyed();

    $header = [
      ['data' => $this->t('Count'), 'field' => 'count'],
      ['data' => $this->t('Path'), 'field' => 'path'],
    ];

    $rows = [];
    foreach ($records as $path => $count) {
      $rows[] = ['data' => ['count' => $count, 'path' => $path]];
    }

    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $table;
  }

}
