<?php

/**
 * @file
 * Training Hub shared Algolia index settings.
 *
 * Only Acquia production writes to the Training Hub. Every other environment
 * gets no credentials and no index name, so it cannot write to the hub at
 * all. For local development, set all four values in the site's
 * local.settings.php, with an index name other than the production one.
 *
 * @see docs/AlgoliaSearch.md
 */

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

if (EnvironmentDetector::isProdEnv()) {
  $federated_app_id = getenv('HS_ALGOLIA_FEDERATED_APP_ID', TRUE) ?: getenv('HS_ALGOLIA_FEDERATED_APP_ID');
  $federated_write_key = getenv('HS_ALGOLIA_FEDERATED_WRITE_KEY', TRUE) ?: getenv('HS_ALGOLIA_FEDERATED_WRITE_KEY');

  // Without credentials the index gets no name either, so it does not write.
  if ($federated_app_id && $federated_write_key) {
    $config['search_api.server.hs_algolia_federated']['backend_config']['application_id'] = $federated_app_id;
    $config['search_api.server.hs_algolia_federated']['backend_config']['api_key'] = $federated_write_key;
    $settings['hs_algolia_federated_search_key'] = (getenv('HS_ALGOLIA_FEDERATED_SEARCH_KEY', TRUE) ?: getenv('HS_ALGOLIA_FEDERATED_SEARCH_KEY')) ?: '';
    $config['search_api.index.hs_algolia_trainings']['options']['algolia_index_name'] = 'hsdp_trainings';
  }
}
