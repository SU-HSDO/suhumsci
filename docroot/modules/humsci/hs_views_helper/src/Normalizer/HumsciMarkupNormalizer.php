<?php

namespace Drupal\hs_views_helper\Normalizer;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\serialization\Normalizer\MarkupNormalizer;

/**
 * Class HumsciMarkupNormalizer.
 *
 * @package Drupal\hs_views_helper\Normalizer
 */
class HumsciMarkupNormalizer extends MarkupNormalizer {

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * HumsciMarkupNormalizer constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('hs_views_helper');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $normalized = parent::normalize($object, $format, $context);
    if (strpos($normalized, 'data-attribute-tag') !== FALSE) {
      $normalized = $this->parseMultipleFields($normalized);
    }
    return $normalized;
  }

  /**
   * Parse the data output and build a multi-value array.
   *
   * @param string $data
   *   Markup html string.
   *
   * @return array|string
   *   Associated array with html text as the values.
   */
  protected function parseMultipleFields($data) {
    try {
      $dom = new \DOMDocument();
      $dom->loadHTML($data);
      $xpath = new \DOMXPath($dom);
    }
    catch (\Exception $e) {
      $this->logger->error('Unable to parse multiple field data. Message: @message', [
        '@message',
        $e->getMessage(),
      ]);
      return $data;
    }

    $key = $xpath->query('//@data-attribute-tag')->item(0)->nodeValue;
    $results = $xpath->query('//div[@data-attribute-tag]');
    $values = [];
    for ($i = 0; $i < $results->length; $i++) {
      $item = $results->item($i);
      $values[] = trim($item->textContent);
    }
    $data_array = [$key => $values];
    return $values ? $data_array : $data;
  }

}
