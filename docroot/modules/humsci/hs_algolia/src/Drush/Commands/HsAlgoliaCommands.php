<?php

namespace Drupal\hs_algolia\Drush\Commands;

use Algolia\AlgoliaSearch\Exceptions\ExceededRetriesException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api_algolia\Plugin\search_api\backend\SearchApiAlgoliaBackend;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for the Algolia indexes.
 */
final class HsAlgoliaCommands extends DrushCommands {

  /**
   * Search API index whose records land in the shared Training Hub index.
   *
   * Matches HS_ALGOLIA_FEDERATED_INDEX_ID, repeated so the settings can be
   * built without the module file loaded.
   */
  const FEDERATED_INDEX_ID = 'hs_algolia_trainings';

  /**
   * Record attributes the Training Hub can facet or filter on.
   *
   * Term names and the site name are searchable so a long facet list can be
   * filtered by typing. The site ID is the stable key for filtering by site.
   * The no-date flag only separates undated trainings, so it needs no counts.
   */
  const FEDERATED_FACETS = [
    'site_id',
    'searchable(site_name)',
    'searchable(training_name)',
    'searchable(provider)',
    'searchable(audience)',
    'searchable(product)',
    'searchable(unit)',
    'delivery_method',
    'filterOnly(nodate)',
  ];

  /**
   * Algolia's default ranking criteria, which sort replicas keep after theirs.
   */
  const DEFAULT_RANKING = [
    'typo',
    'geo',
    'words',
    'filters',
    'proximity',
    'attribute',
    'exact',
    'custom',
  ];

  /**
   * Times to check whether Algolia has applied the primary index settings.
   */
  const WAIT_MAX_RETRIES = 30;

  /**
   * Longest pause between those checks, in milliseconds.
   *
   * Algolia backs off by 200ms per retry up to this, so the wait gives up
   * after about 50 seconds.
   */
  const WAIT_MAX_INTERVAL = 2000;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Push facet and sort settings to the shared Training Hub index.
   *
   * Algolia only facets on attributes it has been told about, and sorting
   * needs a replica index per sort order. Running this again reapplies the
   * same settings, so it is safe to repeat on any participating site.
   */
  #[CLI\Command(name: 'humsci:algolia:configure-federated')]
  #[CLI\Usage(name: 'drush @<SITE_NAME>.<ENV> humsci:algolia:configure-federated', description: 'Apply facet and sort settings to the Training Hub index for that environment.')]
  public function configureFederated(): void {
    /** @var \Drupal\search_api\IndexInterface|null $index */
    $index = $this->entityTypeManager->getStorage('search_api_index')->load(self::FEDERATED_INDEX_ID);
    $index_name = (string) $index?->getOption('algolia_index_name');
    if ($index_name === '') {
      throw new \RuntimeException('The Training Hub index has no Algolia index name. It is set in settings.php.');
    }

    $backend = $index->hasValidServer() ? $index->getServerInstance()->getBackend() : NULL;
    if (!$backend instanceof SearchApiAlgoliaBackend) {
      throw new \RuntimeException('The Training Hub index is not on an Algolia server.');
    }

    $credentials = $backend->getConfiguration();
    if (empty($credentials['application_id']) || empty($credentials['api_key'])) {
      throw new \RuntimeException('The Training Hub server has no Algolia credentials. They are set in settings.php from environment variables.');
    }

    // Records link back to their own site.
    if (!$this->configFactory->get('domain_301_redirect.settings')->get('domain')) {
      $this->logger()->warning('This site has no canonical domain in domain_301_redirect.settings, so its Training Hub records may link to an internal or drush host. Set the domain before indexing this site.');
    }

    $client = $backend->getAlgolia();
    $settings = self::buildFederatedSettings($index_name);

    // Forwarding keeps the replicas' facets in step with the primary.
    $response = $client->setSettings($index_name, $settings[$index_name], TRUE);

    try {
      $client->waitForTask($index_name, $response['taskID'], [], self::WAIT_MAX_RETRIES, self::WAIT_MAX_INTERVAL);
    }
    catch (ExceededRetriesException $e) {
      throw new \RuntimeException("Algolia has not yet applied the settings for $index_name, so its sort replicas were not configured. The settings may still apply asynchronously. Run this command again in a few minutes.", 0, $e);
    }
    $this->logger()->success('Configured Algolia index {name}.', ['name' => $index_name]);
    unset($settings[$index_name]);

    foreach ($settings as $name => $replica_settings) {
      $client->setSettings($name, $replica_settings, FALSE);
      $this->logger()->success('Sent settings for Algolia replica {name}.', ['name' => $name]);
    }
  }

  /**
   * Build the settings for the Training Hub index and its sort replicas.
   *
   * @param string $index_name
   *   Algolia index name for the current environment.
   *
   * @return array
   *   Settings keyed by Algolia index name, primary index first.
   */
  public static function buildFederatedSettings(string $index_name): array {
    $settings = [
      $index_name => [
        'attributesForFaceting' => self::FEDERATED_FACETS,
        'replicas' => [],
      ],
    ];

    foreach (['asc', 'desc'] as $direction) {
      $replica = $index_name . '_date_start_' . $direction;
      $settings[$index_name]['replicas'][] = $replica;
      $settings[$replica] = [
        'attributesForFaceting' => self::FEDERATED_FACETS,
        'ranking' => array_merge(["$direction(date_start)"], self::DEFAULT_RANKING),
      ];
    }

    return $settings;
  }

}
