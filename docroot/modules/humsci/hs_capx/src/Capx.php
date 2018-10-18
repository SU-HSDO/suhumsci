<?php

namespace Drupal\hs_capx;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service to test CAPx connection and import organization data.
 */
class Capx {

  /**
   * API url used for organization data.
   */
  const API_URL = 'https://api.stanford.edu';

  /**
   * Authentication url.
   */
  const AUTH_URL = 'https://authz.stanford.edu/oauth/token';

  /**
   * The actual CAP API.
   */
  const CAP_URL = 'https://cap.stanford.edu/cap-api/api/profiles/v1';

  /**
   * CAPx API username.
   *
   * @var string
   */
  protected $username;

  /**
   * CAPx API password.
   *
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
   * Set the CAPx Username.
   *
   * @param string $username
   *   Username.
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * Set the CAPx Password.
   *
   * @param string $password
   *   Password.
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * Get the url for CAPx for the given organizations.
   *
   * @param string $organizations
   *   Comma separated organization codes.
   * @param bool $children
   *   Include all children of the organizations.
   *
   * @return string
   *   CAPx URL.
   */
  public static function getOrganizationUrl($organizations, $children = FALSE) {
    $organizations = preg_replace('/[^A-Z,]/', '', strtoupper($organizations));
    $url = self::CAP_URL . "?orgCodes=$organizations&ps=1000";
    if ($children) {
      $url .= '&includeChildren=true';
    }
    return $url;
  }

  /**
   * Get the url for CAPx for given workgroups.
   *
   * @param string $workgroups
   *   Commas separated list of workgroups.
   *
   * @return string
   *   CAPx URL.
   */
  public static function getWorkgroupUrl($workgroups) {
    $workgroups = preg_replace('/[^A-Z,:]/', '', strtoupper($workgroups));
    return self::CAP_URL . "?privGroups=$workgroups&ps=1000";
  }

  /**
   * Test the connection with the username and passwords is valid.
   *
   * @return bool
   *   The connection was successful.
   */
  public function testConnection() {
    $parameters = ['grant_type' => 'client_credentials'];

    try {
      $response = $this->client->request('GET', self::AUTH_URL, [
        'query' => $parameters,
        'auth' => [$this->username, $this->password],
      ]);
    }
    catch (GuzzleException $e) {
      // Most errors originate from the API itself.
      return FALSE;
    }

    // Just in case the API doesn't respond with an error, we also check the
    // status code.
    return $response->getStatusCode() == 200;
  }

  /**
   * Sync the organization database with the api data from CAP.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function syncOrganizations() {
    $this->insertOrgData($this->getOrgData());
  }

  /**
   * Insert the given organization data into the database.
   *
   * @param array $org_data
   *   Keyed array of organization data.
   * @param array $parent
   *   The organization parent if one exists.
   *
   * @throws \Exception
   */
  protected function insertOrgData(array $org_data, $parent = []) {
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
   * Get the organization data array from the API.
   *
   * @return array
   *   Keyed array of all organization data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getOrgData() {
    if ($cache = $this->cache->get('capx:org_data')) {
      return $cache->data;
    }

    $token = $this->getAccessToken();

    // AA00 is the root level of all Stanford.
    $result = $this->client->request('GET', self::API_URL . '/cap/v1/orgs/AA00', ['query' => ['access_token' => $token]]);
    $result = json_decode($result->getBody()->getContents(), TRUE);
    $this->cache->set('capx:org_data', $result, time() + 60 * 80 * 24 * 7, [
      'capx',
      'capx:ord-data',
    ]);
    return $result;
  }

  /**
   * Get the API token for CAP.
   *
   * @return string
   *   API Token.
   *
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
    $this->cache->set('capx:access_token', $result, time() + $result['expires_in'], [
      'capx',
      'capx:token',
    ]);
    return $result['access_token'];
  }

}
