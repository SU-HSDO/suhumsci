<?php

namespace Drupal\hs_capx;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class capx:
 *
 * @package Drupal\hs_capx
 */
class Capx {

  const API_URL = 'https://api.stanford.edu';

  const AUTH_URL = 'https://authz.stanford.edu/oauth/token';

  /**
   * @var string
   */
  protected $username;

  /**
   * @var string
   */
  protected $password;

  /**
   * Capx constructor.
   *
   * @param \GuzzleHttp\ClientInterface $guzzle
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(ClientInterface $guzzle, CacheBackendInterface $cache, Connection $database) {
    $this->client = $guzzle;
    $this->cache = $cache;
    $this->database = $database;
  }

  /**
   * @param $username
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * @param $password
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * @return bool
   */
  public function testConnection() {
    $parameters = ['grant_type' => 'client_credentials'];

    try {
      $response = $this->client->request('GET', self::AUTH_URL, [
        'query' => $parameters,
        'auth' => [$this->username, $this->password],
      ]);
    }
    catch (ClientException $e) {
      return FALSE;
    }

    return $response->getStatusCode() == 200;
  }

  /**
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function syncOrganizations() {
    $this->insertOrgData($this->getOrgData());
  }

  /**
   * @param $org_data
   * @param array $parent
   *
   * @throws \Exception
   */
  protected function insertOrgData($org_data, $parent = []) {
    if (!empty($org_data['children'])) {
      foreach ($org_data['children'] as $child) {
        $this->insertOrgData($child, $org_data);
      }
    }

    $insert_data = [
      'name' => $org_data['name'],
      'alias' => $org_data['alias'],
      'orgcodes' => serialize($org_data['orgCodes']),
      'parent' => $parent ? $parent['alias'] : '',
    ];

    $this->database->merge('hs_capx_organizations')
      ->fields($insert_data)
      ->key('alias', $insert_data['alias'])
      ->execute();
  }

  /**
   * @return mixed|\Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getOrgData() {
    if ($cache = $this->cache->get('capx:org_data')) {
      return $cache->data;
    }

    $token = $this->getAccessToken();

    $result = $this->client->request('GET', self::API_URL . '/cap/v1/orgs/AA00', ['query' => ['access_token' => $token]]);
    $result = json_decode($result->getBody()->getContents(), TRUE);
    $this->cache->set('capx:org_data', $result, time() + 60 * 80 * 24 * 7, [
      'capx',
      'capx:ord-data',
    ]);
    return $result;
  }

  /**
   * @return mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getAccessToken() {
    if ($cache = $this->cache->get('capx:access_token')) {
      return $cache->data['access_token'];
    }

    $parameters = ['grant_type' => 'client_credentials'];
    $result = $this->client->request('GET', self::AUTH_URL, [
      'query' => $parameters,
      'auth' => [$this->username, $this->password],
    ]);

    $result = json_decode($result->getBody()->getContents(), TRUE);
    $this->cache->set('capx:access_token', $result, time() + $result['expires_in'],[
      'capx',
      'capx:token',
    ]);
    return $result['access_token'];
  }

}
