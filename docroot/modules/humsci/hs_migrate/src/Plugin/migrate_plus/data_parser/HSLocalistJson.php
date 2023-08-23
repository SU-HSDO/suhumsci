<?php

namespace Drupal\hs_migrate\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for Capx Publications migrations.
 *
 * @DataParser(
 *   id = "hs_localist_json",
 *   title = @Translation("HSPubJson")
 * )
 */
class HSLocalistJson extends Json {

  /**
   * {@inheritDoc}
   */
  protected function getSourceData(string $url): array {
    $source_data = [];
    foreach (self::getPagedUrls($url) as $page) {
      $source_data = [...$source_data, ...parent::getSourceData($page)];
    }

    $modified_data = [];
    foreach ($source_data as $item) {
      if (isset($modified_data[$item['event']['id']])) {
        $modified_data[$item['event']['id']]['event']['event_instances'][] = $item['event']['event_instances'][0];
      }
      else {
        $modified_data[$item['event']['id']] = $item;
      }
    }
    return array_values($modified_data);
  }

  /**
   * Using the given url, get an array of pages to fetch all events.
   *
   * @param string $url
   *   Original url.
   *
   * @return string[]
   *   Paged url results.
   */
  protected static function getPagedUrls(string $url): array {
    $query = parse_url($url, PHP_URL_QUERY);
    $base_url = trim(str_replace($query, '', $url), '?');
    parse_str($query, $query_parts);

    // Fetch only 1 event to make things as fast as possible.
    $query_parts['pp'] = 1;
    $query = http_build_query($query_parts);

    // Query the API using the given base url and all other query parts.
    try {
      $results = json_decode((string) \Drupal::httpClient()
        ->request('GET', "$base_url?$query")
        ->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable $e) {
      // In case something errors, just return the original url.
      return [$url];
    }
    $total_count = $results['page']['total'];

    $paged_urls = [];
    for ($page = 1; $page <= ceil($total_count / 100); $page++) {
      // The maximum count per page is 100.
      $query_parts['pp'] = 100;
      $query_parts['page'] = $page;

      $query = http_build_query($query_parts);
      $paged_urls[] = "$base_url?$query";
    }
    return $paged_urls;
  }

}
