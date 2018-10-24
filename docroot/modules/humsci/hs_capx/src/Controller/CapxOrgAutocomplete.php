<?php

namespace Drupal\hs_capx\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CapxOrgAutocomplete.
 *
 * @package Drupal\hs_capx\Controller
 */
class CapxOrgAutocomplete extends ControllerBase {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Get organizations that match the autocomplete text entry.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Ajax Reqeust.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Found results response.
   */
  public function autocomplete(Request $request) {
    $string = Xss::filter(Html::escape(mb_strtolower($request->query->get('q'))));
    /** @var \Drupal\Core\Database\Statement $aresults */
    $query_results = $this->database->select('hs_capx_organizations', 'c')
      ->fields('c', [
        'alias',
        'orgcodes',
        'name',
      ])
      ->condition('name', "%$string%", 'LIKE')
      ->range(0, 10)
      ->execute()
      ->fetchAllAssoc('alias');

    $results = [];
    foreach ($query_results as $item) {
      $org_codes = unserialize($item->orgcodes);
      $results[] = ['value' => end($org_codes), 'label' => $item->name];
    }
    return new JsonResponse($results);
  }

}
