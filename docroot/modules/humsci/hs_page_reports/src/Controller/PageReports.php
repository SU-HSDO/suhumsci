<?php

namespace Drupal\hs_page_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PageReports extends ControllerBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * PageReports constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   */
  public function __construct(Connection $db_connection) {
    $this->database = $db_connection;
  }

  /**
   * Get a table of the top 404 reports.
   *
   * @return array
   *   Page render array.
   */
  public function pageNotFoundReport() {
    $build = [];
    $build['report_table'] = $this->getTable(404);
    return $build;
  }

  /**
   * Get a table of the top 403 reports.
   *
   * @return array
   *   Page render array.
   */
  public function accessDeniedReport() {
    $build = [];
    $build['report_table'] = $this->getTable(403);
    return $build;
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
