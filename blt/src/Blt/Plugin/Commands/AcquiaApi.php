<?php

namespace Example\Blt\Plugin\Commands;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Exception\GuzzleException;
use Robo\Tasks;

/**
 * Class AcquiaApi.
 *
 * @package Example\Blt\Plugin\Commands
 */
class AcquiaApi extends Tasks {

  /**
   * Keyed array of environment IDs.
   *
   * Keys should be the environment and the values should be the UUID of the
   * environment on Acquia hosting. The key 'appId' should be added to identify
   * the Acquia cloud application.
   *
   * @var array
   */
  protected $envIds = [];

  /**
   * Acquia API Key.
   *
   * @var string
   */
  protected $key;

  /**
   * Acquia API Secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * AcquiaApi constructor.
   *
   * @param array $env_ids
   *   Keyed array of Acquia environment IDS.
   * @param string $apiKey
   *   Acquia API Key.
   * @param string $apiSecret
   *   Acquia API Secret.
   */
  public function __construct(array $env_ids, $apiKey = '', $apiSecret = '') {
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
   * Add a domain to a given environment.
   *
   * @param string $environment
   *   Environment to effect.
   * @param string $domain
   *   Domain to add: foo.stanford.edu.
   *
   * @return bool|string
   *   API Response.
   */
  public function addDomain($environment, $domain) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/domains", 'POST', ['json' => ['hostname' => $domain]]);
  }

  public function getDatabases($environment) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/databases");
  }

  public function getDatabaseBackups($environment, $databaseName){
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/databases/$databaseName/backups");
  }

  public function deleteDatabaseBackup($environment, $databaseName, $backupId){
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/databases/$databaseName/backups/$backupId", 'DELETE');
  }

  /**
   * Add a database to all environments.
   *
   * @param string $db_name
   *   Database name to add.
   *
   * @return bool|string
   *   API Response.
   */
  public function addDatabase($db_name) {
    return $this->callAcquiaApi("/applications/{$this->envIds['appId']}/databases", 'POST', ['json' => ['name' => $db_name]]);
  }

  /**
   * Add an SSL Certificate to a given environment.
   *
   * @param string $environment
   *   Environment to effect.
   * @param string $cert
   *   SSL Cert file contents.
   * @param string $key
   *   SSL Key file contents.
   * @param string $intermediate
   *   SSL Intermediate certificate file contents.
   * @param string|null $label
   *   Label for Acquia dashboard.
   *
   * @return bool|string
   *   API Response.
   */
  public function addCert($environment, $cert, $key, $intermediate, $label = NULL) {
    if (is_null($label)) {
      $label = date('Y-m-d G:i');
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
   * Activate an SSL cert already installed on the environment.
   *
   * @param string $environment
   *   Environment to effect.
   * @param int $certId
   *   Certificate ID to activate.
   *
   * @return bool|string
   *   API Response.
   */
  public function activateCert($environment, $certId) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates/{$certId}/actions/activate", 'POST');
  }

  /**
   * Remove a cert from the environment.
   *
   * @param string $environment
   *   Environment to effect.
   * @param int $certId
   *   Certificate ID to remove.
   *
   * @return bool|string
   *   API Response.
   */
  public function removeCert($environment, $certId) {
    return $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates/{$certId}", 'DELETE');
  }

  /**
   * Get all SSL certs that are on the environment.
   *
   * @param string $environment
   *   Environment to effect.
   *
   * @return bool|array
   *   API Response.
   */
  public function getCerts($environment) {
    if ($response = $this->callAcquiaApi("/environments/{$this->envIds[$environment]}/ssl/certificates")) {
      return json_decode($response, TRUE);
    }
    return FALSE;
  }

  /**
   * Deploy a git branch or tag to a certain environment.
   *
   * @param string $environment
   *   Environment to effect.
   * @param string $reference
   *   Git branch or tag name.
   *
   * @return bool|string
   *   API Response.
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
      $this->say($e->getMessage());
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
      $this->say($e->getMessage());
      return FALSE;
    }
  }

}
