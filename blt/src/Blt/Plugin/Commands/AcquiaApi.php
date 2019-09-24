<?php

namespace Example\Blt\Plugin\Commands;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class AcquiaApi
 *
 * @package Example\Blt\Plugin\Commands
 */
class AcquiaApi {

  /**
   * @var array
   */
  protected $envIds = [];

  /**
   * @var string
   */
  protected $key;

  /**
   * @var string
   */
  protected $secret;

  /**
   * AcquiaApi constructor.
   *
   * @param array $env_ids
   * @param string $apiKey
   * @param string $apiSecret
   */
  public function __construct($env_ids = [], $apiKey = '', $apiSecret = '') {
    $this->envIds = $env_ids;

    $this->key = $apiKey;
    if (!$this->key && isset($_ENV['ACP_KEY'])) {
      $this->key = $_ENV['ACP_KEY'];
    }

    $this->secret = $apiSecret;
    if (!$this->secret && isset($_ENV['ACP_SECRET'])) {
      $this->secret = $_ENV['ACP_SECRET'];
    }
  }

  /**
   * @param $environment
   * @param $domain
   *
   * @return bool|string
   */
  public function addDomain($environment, $domain) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/domains", 'POST', ['json' => ['hostname' => $domain]]);
  }

  /**
   * @param $db_name
   *
   * @return bool|string
   */
  public function addDatabase($db_name) {
    return $this->callAcquiaApi("/applications/{$this->envIds['appId']}/databases", 'POST', ['json' => ['name' => $db_name]]);
  }

  /**
   * @param $environment
   * @param $cert
   * @param $key
   * @param $intermediate
   * @param null $label
   *
   * @return bool|string
   */
  public function addSSLCert($environment, $cert, $key, $intermediate, $label = NULL) {
    if (is_null($label)) {
      $label = date('Y-m-d h:m');
    }
    $data = [
      'json' => [
        'legacy' => FALSE,
        'certificate' => $cert,
        'private_key' => $key,
        'ca_certificates' => $intermediate,
        'label' => $label,
      ],
    ];
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates", 'POST', $data);
  }

  /**
   * @param $environment
   * @param $certId
   *
   * @return bool|string
   */
  public function activateSSLCert($environment, $certId) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates/{$certId}/actions/activate", 'POST');
  }

  /**
   * @param $environment
   *
   * @return bool|mixed|string
   */
  public function getSSLCerts($environment) {
    if ($response = $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates")) {
      return json_decode($response, TRUE);
    }
    return $response;
  }

  /**
   * @param $environment
   * @param $reference
   *
   * @return bool|string
   */
  public function deployCode($environment, $reference) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/code/actions/switch", 'POST', ['json' => ['name' => $reference]]);
  }

  /**
   * Make an API call to Acquia Cloud API V2.
   *
   * @param string $path
   *   API Endpoint, options from: https://cloudapi-docs.acquia.com/.
   * @param string $method
   *   Request method: GET, POST, PUT, DELETE.
   * @param array $options
   *   Request options for post json data or headers.
   *
   * @return bool|string
   *   False if it fails, api response string if success.
   *
   * @see https://docs.acquia.com/acquia-cloud/develop/api/auth/
   */
  protected function callAcquiaApi($path, $method = 'GET', array $options = []) {
    try {
      $provider = new GenericProvider([
        'clientId' => $this->key,
        'clientSecret' => $this->secret,
        'urlAuthorize' => '',
        'urlAccessToken' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'urlResourceOwnerDetails' => '',
      ]);

      // Try to get an access token using the client credentials grant.
      $accessToken = $provider->getAccessToken('client_credentials');
    }
    catch (\Exception $e) {
      echo $e->getMessage();
      return FALSE;
    }

    // Generate a request object using the access token.
    $request = $provider->getAuthenticatedRequest(
      $method,
      'https://cloud.acquia.com/api/' . ltrim($path, '/'),
      $accessToken
    );

    // Send the request.
    $client = new Client();
    try {
      $response = $client->send($request, $options);
      return (string) $response->getBody();
    }
    catch (GuzzleException $e) {
      echo $e->getMessage();
      return FALSE;
    }
  }

}
