<?php

namespace Drupal\hs_capx\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\DataParserPluginBase;


/**
 * Obtain JSON data for Capx Publications migrations.
 *
 * @DataParser(
 *   id = "hspubjson",
 *   title = @Translation("HSPubJson")
 * )
 */
class HSPubJson extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Iterator over the JSON data.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   *
   * @return array
   *   The selected data to be iterated.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Flow\JSONPath\JSONPathException
   */
  protected function getSourceData($url) {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    // Convert objects to associative arrays.
    $source_data = json_decode($response, TRUE);

    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($source_data)) {
      $utf8response = utf8_encode($response);
      $source_data = json_decode($utf8response, TRUE);
    }

    // Ignore itemSelector configuration. Manually collapse nested publications
    // from each profile into a single array of publications. Also push specific
    // profile data points into each publication.
    $publications_data = [];
    foreach($source_data['values'] as $capx_profile) {
      if($capx_profile['publications']) {
        foreach($capx_profile['publications'] as $publication) {
          $publication_tmp = $publication;
          if(isset($capx_profile['uid'])) {
            $publication_tmp['uid'] = $capx_profile['uid'];
          }
          $publications_data[] = $publication_tmp;
        }
      }
    }

    return $publications_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    // Default implementation from JSON parser. Abstract method so requires
    // implemenation.
    // @see Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    // Default implementation from JSON parser. Abstract method so requires
    // implemenation.
    // @see Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $field_data = $current;
        $field_selectors = explode('/', trim($selector, '/'));
        foreach ($field_selectors as $field_selector) {
          if (is_array($field_data) && array_key_exists($field_selector, $field_data)) {
            $field_data = $field_data[$field_selector];
          }
          else {
            $field_data = '';
          }
        }
        $this->currentItem[$field_name] = $field_data;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }

}
