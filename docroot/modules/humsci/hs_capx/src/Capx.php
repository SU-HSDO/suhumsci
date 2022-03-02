<?php

namespace Drupal\hs_capx;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * Guzzle client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Database logging service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Capx constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Database logging service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(CacheBackendInterface $cache, Connection $database, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->cache = $cache;
    $this->database = $database;
    $this->logger = $logger_factory->get('capx');
    $capx_settings = $config_factory->get('hs_capx.settings');
    if ($username = $capx_settings->get('username')) {
      $this->setUsername($username);
      try {
        $key = $entity_type_manager->getStorage('key')
          ->load($capx_settings->get('password'));
        $password = $key->getKeyValue();
        $this->setPassword($password);
      }
      catch (\Throwable $e) {
        $this->logger->error('Unable to load key entity: @id', ['@id' => $capx_settings->get('password')]);
      }
    }
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
   * Call the API and return the response.
   *
   * @param string $url
   *   API Url.
   * @param array $options
   *   Guzzle request options.
   *
   * @return bool|string
   *   Response string or false if failed.
   */
  protected static function getApiResponse($url, array $options = []) {
    /** @var \GuzzleHttp\ClientInterface $guzzle */
    $guzzle = \Drupal::service('http_client');
    try {
      $response = $guzzle->request('GET', $url, $options);
    }
    catch (GuzzleException $e) {
      // Most errors originate from the API itself.
      \Drupal::logger('capx')->error($e->getMessage());
      return FALSE;
    }
    return $response->getStatusCode() == 200 ? (string) $response->getBody() : FALSE;
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
   *   CAPx URLs.
   */
  public static function getOrganizationUrl($organizations, $children = FALSE) {
    $organizations = preg_replace('/[^A-Z,]/', '', strtoupper($organizations));
    $url = self::CAP_URL . "?orgCodes=$organizations";
    if ($children) {
      $url .= '&includeChildren=true';
    }
    return $url . '&filter=publications.featured:equals:true';
  }

  /**
   * Get the url for CAPx for given workgroups.
   *
   * @param string $workgroups
   *   Commas separated list of workgroups.
   *
   * @return string
   *   CAPx URLs.
   */
  public static function getWorkgroupUrl($workgroups) {
    $workgroups = preg_replace('/[^A-Z,:\-_]/', '', strtoupper($workgroups));
    return self::CAP_URL . "?privGroups=$workgroups&filter=publications.featured:equals:true";
  }

  /**
   * Get the total number of profiles for the given cap url.
   *
   * @param string $url
   *   Cap API Url.
   *
   * @return int
   *   Total number of profiles.
   */
  public function getTotalProfileCount($url) {
    $token = $this->getAccessToken();
    $response = self::getApiResponse("$url&ps=1&access_token=$token");
    if ($response) {
      $response = json_decode($response, TRUE);
      return $response['totalCount'] ?? 0;
    }
  }

  /**
   * Test the connection with the username and passwords is valid.
   *
   * @return bool
   *   The connection was successful.
   */
  public function testConnection() {
    $options = [
      'query' => ['grant_type' => 'client_credentials'],
      'auth' => [$this->username, $this->password],
    ];
    return self::getApiResponse(self::AUTH_URL, $options);
  }

  /**
   * Sync the organization database with the api data from CAP.
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
  protected function insertOrgData(array $org_data, array $parent = []) {
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
   */
  protected function getOrgData() {
    if ($cache = $this->cache->get('capx:org_data')) {
      return $cache->data;
    }

    $options = ['query' => ['access_token' => $this->getAccessToken()]];
    // AA00 is the root level of all Stanford.
    $result = self::getApiResponse(self::API_URL . '/cap/v1/orgs/AA00', $options);

    if ($result) {
      $result = json_decode($result, TRUE);
      $this->cache->set('capx:org_data', $result, time() + 60 * 60 * 24 * 7, [
        'capx',
        'capx:ord-data',
      ]);
      return $result;
    }
    return [];
  }

  /**
   * Get the API token for CAP.
   *
   * @return string
   *   API Token.
   */
  protected function getAccessToken() {
    if ($cache = $this->cache->get('capx:access_token')) {
      return $cache->data['access_token'];
    }

    $options = [
      'query' => ['grant_type' => 'client_credentials'],
      'auth' => [$this->username, $this->password],
    ];
    if ($result = self::getApiResponse(self::AUTH_URL, $options)) {
      $result = json_decode($result, TRUE);
      $this->cache->set('capx:access_token', $result, time() + $result['expires_in'], [
        'capx',
        'capx:token',
      ]);
      return $result['access_token'];
    }
  }

}
