<?php

namespace Drupal\hs_siteimprove;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Defines the SiteImprove service class.
 */
class SiteImprove implements SiteImproveInterface {

  /**
   * API request timeout in seconds.
   */
  const int REQUEST_TIMEOUT = 30;

  /**
   * API connection timeout in seconds.
   */
  const int CONNECT_TIMEOUT = 10;

  /**
   * The base API URL.
   *
   * @var string
   */
  protected string $baseUrl;

  /**
   * The SiteImprove config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Constructor for SiteImprove.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    protected ConfigFactoryInterface $config_factory,
    protected ClientInterface $http_client,
    protected StateInterface $state,
    protected RequestStack $request_stack,
    protected LoggerInterface $logger,
    protected CacheBackendInterface $cache,
  ) {
    $this->config = $config_factory->get('hs_siteimprove.settings');
    $this->baseUrl = $this->config->get('base_url') ?: 'https://api.us.siteimprove.com/v2';
    $this->cache = $cache;
  }

  /**
   * Is there enough config info to make API calls?
   */
  protected function hasSiteConfig(): bool {
    return !empty($this->getApiKey()) && !empty($this->getUsername());
  }

  /**
   * Get a list of all sites.
   *
   * @param bool $refresh
   *   Force the list to be refetched from the API.
   *
   * @return array
   */
  public function getSites(bool $refresh = FALSE): array {
    $cid = 'hs_siteimprove:sites';
    if (!$refresh && $cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    try {
      $sites = $this->call('GET', '/sites', ['page_size' => 500]);
      // Cache permanently (will be cleared with cache rebuilds)
      $this->cache->set($cid, $sites->items, CacheBackendInterface::CACHE_PERMANENT);
      return $sites->items;
    }
    catch (SiteImproveException $e) {
      $this->logger->error('Failed to fetch sites: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSiteId(bool $refresh = FALSE): ?string {
    if (!$refresh) {
      $site_id = $this->state->get('hs_siteimprove.site_id');
      if ($site_id) {
        return $site_id;
      }
    }

    try {
      $site = $this->getCurrentSite($refresh);
      if ($site?->id) {
        $this->state->set('hs_siteimprove.site_id', $site->id);
        return $site->id;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get current site ID: @message', ['@message' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagesWithBrokenLinks(): ?array {
    if ($cache = $this->cache->get('hs_siteimprove_broken_links')) {
      return $cache->data;
    }

    $site_id = $this->getCurrentSiteId();
    if (!$site_id) {
      return NULL;
    }

    try {
      $pages = $this->call('GET', "/sites/$site_id/quality_assurance/links/pages_with_broken_links", ['page_size' => 5]);
      // Cache for 5 minutes.
      $this->cache->set('hs_siteimprove_broken_links', $pages->items, time() + 900);
      return $pages->items;
    }
    catch (SiteImproveException $e) {
      $this->logger->error('Failed to fetch broken links: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSite(bool $refresh = FALSE): ?object {
    $production_url = '';
    try {
      $site_improve_sites = $this->getSites($refresh);
      if (empty($site_improve_sites)) {
        return NULL;
      }

      // SiteImprove uses the production URL to identify the site.
      $production_url = $this->getProductionUrl();
      foreach ($site_improve_sites as $site) {
        if ($this->getNormalizedUrl($site->url) === $production_url) {
          return $site;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get current site: @message', ['@message' => $e->getMessage()]);
    }
    $this->logger->error('Could not find a SiteImprove site for the site: @production_url', ['@production_url' => $production_url]);

    return NULL;
  }

  /**
   * Makes an HTTP request to the SiteImprove API.
   *
   * @param string $method
   *   The HTTP method to use.
   * @param string $endpoint
   *   The API endpoint.
   * @param array $query
   *   Optional query parameters.
   *
   * @return object
   *   The API response.
   *
   * @throws \Drupal\hs_siteimprove\SiteImproveException
   */
  protected function call(string $method, string $endpoint, array $query = []): object {
    $this->validateConfiguration();

    $options = [
      RequestOptions::HEADERS => [
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode($this->getUsername() . ':' . $this->getApiKey()),
      ],
      RequestOptions::QUERY => $query,
      RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT,
      RequestOptions::CONNECT_TIMEOUT => self::CONNECT_TIMEOUT,
    ];

    try {
      $response = $this->http_client->request($method, $this->baseUrl . $endpoint, $options);
      $response_body = json_decode($response->getBody(), FALSE, 16, JSON_THROW_ON_ERROR);

      if ($response->getStatusCode() === 200) {
        unset($response_body->ErrorCode, $response_body->ErrorMessage);
        return $response_body;
      }

      throw new SiteImproveException('API request failed with status code: ' . $response->getStatusCode());
    }
    catch (\Exception $e) {
      throw new SiteImproveException('API request failed: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Validates the service configuration.
   *
   * @throws \Drupal\hs_siteimprove\SiteImproveException
   */
  protected function validateConfiguration(): void {
    if (empty($this->baseUrl)) {
      throw new SiteImproveException('Base URL must be configured on the module settings page');
    }
    if (!$this->hasSiteConfig()) {
      throw new SiteImproveException('SiteImprove API credentials must be configured on the module settings page');
    }
  }

  /**
   * Gets the API key from the configuration.
   *
   * @return string
   *   The API key.
   */
  protected function getApiKey(): string {
    return $this->config->get('api_key') ?: '';
  }

  /**
   * Gets the username from configuration.
   *
   * @return string
   *   The username.
   */
  protected function getUsername(): string {
    return $this->config->get('username') ?: '';
  }

  /**
   * Normalizes a URL by removing protocol and trailing slash.
   *
   * @param string $url
   *   The URL to normalize.
   *
   * @return string
   *   The normalized URL.
   */
  protected function getNormalizedUrl(string $url): string {
    return preg_replace('#^https?://#', '', rtrim($url, '/'));
  }

  /**
   * Gets the current normalized production URL.
   *
   * @return string
   *   The normalized production URL.
   */
  protected function getProductionUrl(): string {
    $current_url = $this->request_stack->getCurrentRequest()->getSchemeAndHttpHost();
    $production_url = $this->getNormalizedUrl($current_url);
    $environment = getenv('AH_SITE_ENVIRONMENT');
    // 'stage' environment name is named 'test' internally in Acquia.
    $environment = $environment === 'stage' ? 'test' : $environment;

    $site_identifier = explode('.', $production_url)[0];

    if ($environment && str_ends_with($site_identifier, $environment)) {
      // Handle Acquia URLs.
      $site_identifier = str_replace('-' . $environment, '', $site_identifier);
    }
    elseif (str_contains($production_url, '.tugboatqa.com')) {
      // Handle tugboat URLs.
      $site_identifier = preg_replace('/-[^-]*$/', '', $site_identifier);
    }

    return $site_identifier . '.stanford.edu';
  }

}
