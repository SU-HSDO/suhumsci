<?php

namespace Drupal\hs_capx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CapxOrgAutocomplete
 *
 * @package Drupal\hs_capx\Controller
 */
class CapxOrgAutocomplete extends ControllerBase {

  protected $database;

  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function autocomplete(Request $request) {
    $string = mb_strtolower($request->query->get('q'));
    /** @var \Drupal\Core\Database\Statement $aresults */
    $results = $this->database->select('hs_capx_organizations', 'c')
      ->fields('c', [
        'alias',
        'name',
      ])
      ->condition('name', "%$string%", 'LIKE')
      ->
      ->execute()
      ->fetchAllKeyed();
dpm($results);
    return new JsonResponse($results);
  }

}
