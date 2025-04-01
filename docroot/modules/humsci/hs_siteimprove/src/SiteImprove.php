<?php

namespace Drupal\hs_siteimprove;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Defines the SiteImprove service class.
 */
class SiteImprove implements SiteImproveInterface {

  /**
   * API request timeout in seconds.
   */
  const REQUEST_TIMEOUT = 30;

  /**
   * API connection timeout in seconds.
   */
  const CONNECT_TIMEOUT = 10;

  /**
   * SiteImprove settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The base API URL.
   *
   * @var string
   */
  protected string $baseUrl;

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
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected ClientInterface $http_client,
    protected StateInterface $state,
    protected RequestStack $request_stack,
    protected LoggerInterface $logger,
  ) {
    $this->config = $config_factory->get('hs_siteimprove.settings');
    $this->baseUrl = $this->config->get('base_url') ?: 'https://api.siteimprove.com/v2';
  }

  /**
   * {@inheritdoc}
   */
  public function hasSiteConfig(): bool {
    return !empty($this->getApiKey()) && !empty($this->getUsername());
  }

  /**
   * {@inheritdoc}
   */
  public function getSites(): array {

    try {
      $sites = $this->call('GET', '/sites', ['page_size' => 200]);
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
      $site = $this->getCurrentSite();
      if ($site?->id) {
        $this->state->set('hs_siteimprove.site_id', $site->id);
        return $site->id;
      }
    }
    catch (SiteImproveException $e) {
      $this->logger->error('Failed to get current site ID: @message', ['@message' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagesWithBrokenLinks(): ?array {
    $site_id = $this->getCurrentSiteId();
    if (!$site_id) {
      return NULL;
    }

    try {
      $pages = [];
      $broken_links = $this->call('GET', "/sites/{$site_id}/quality_assurance/links/broken_links", ['page_size' => 300]);

      if (!empty($broken_links->items)) {
        foreach ($broken_links->items as $link) {
          // Get the pages for the broken link.
          $pages_response = $this->call('GET', "/sites/{$site_id}/quality_assurance/links/broken_links/{$link->id}/pages", ['page_size' => 300]);
          if (!empty($pages_response->items)) {
            $pages = array_merge($pages, $pages_response->items);
          }
        }
      }

      return $pages;
    }
    catch (SiteImproveException $e) {
      $this->logger->error('Failed to fetch broken links: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSite(): ?object {
    try {
      $site_improve_sites = $this->getSites();
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
    catch (SiteImproveException $e) {
      $this->logger->error('Failed to get current site: @message', ['@message' => $e->getMessage()]);
    }

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
      $response_body = json_decode($response->getBody());

      if ($response->getStatusCode() === 200) {
        unset($response_body->ErrorCode, $response_body->ErrorMessage);
        return $response_body;
      }

      throw new SiteImproveException('API request failed with status code: ' . $response->getStatusCode());
    }
    catch (GuzzleException $e) {
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
   * Gets the API key from configuration.
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
    $current_url = "https://hs-colorful-cgbxqmnu99lecjrrmzbkvv1egejcvznw.tugboatqa.com/";
    $production_url = $this->getNormalizedUrl($current_url);
    $environment = getenv('AH_SITE_ENVIRONMENT');

    // If the site is not in prod, construct the production URL.
    if (empty($environment) || $environment !== 'prod') {
      $site_identifier = $production_url;

      // Handle tugboat URLs.
      if (str_contains($production_url, '.tugboatqa.com')) {
        // Extract everything before .tugboatqa.com.
        $site_identifier = explode('.tugboatqa.com', $production_url)[0];
        // Remove everything after and including the last dash.
        $site_identifier = preg_replace('/-[^-]*$/', '', $site_identifier);
      }
      // Handle Acquia URLs.
      elseif (str_ends_with($site_identifier, $environment)) {
        $site_identifier = str_replace('-' . $environment, '', $site_identifier);
      }

      $site_identifier = 'west';

      $production_url = $site_identifier . '.stanford.edu';

    }

    return $production_url;
  }

}
