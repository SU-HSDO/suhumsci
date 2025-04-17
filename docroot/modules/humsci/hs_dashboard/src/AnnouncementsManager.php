<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to handle HDSP Announcements.
 */
class AnnouncementsManager implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Announcement CSV location.
   */
  const ANNOUNCEMENTS_CSV = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTQzSuPudq048D1NadRBE9h_s_-w-o4YtcC6AHfCdcqn3gX52akZNOaF5KAG9SeXkCV6PvIVmRtQ0HR/pub?gid=1146337887&single=true&output=csv';

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The HTTP client to fetch announcement data.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger channel service.
   *
   * @var Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The file system interface.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Date formatter interface.
   *
   * @var Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param GuzzleHttp\ClientInterface $http_client
   *   The guzzle http client.
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger interface.
   * @param Drupal\Core\File\FileSystemInterface $file_system
   *   The logger interface.
   * @param Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter interface.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system,
    DateFormatterInterface $date_formatter,
    CacheBackendInterface $cache,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('hs_dashboard');
    $this->fileSystem = $file_system;
    $this->dateFormatter = $date_formatter;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('file_system'),
      $container->get('date.formatter'),
      $container->get('cache.default'),
    );
  }

  /**
   * Retrieves the CSV from Google Sheets.
   *
   * @param string $url
   *   The URL of the CSV file to retrieve.
   *
   * @return array
   *   Returns a csv array - if one is not found, an empty array is returned.
   */
  private function getCsvAnnouncements($url): array {
    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Accept' => 'text/csv',
        ],
        'timeout' => 10,
      ]);

      if ($response->getStatusCode() !== 200) {
        $this->logger->error('Invalid response status from {url} { message }: ', [
          'url' => $url,
          'message' => $response->getStatusCode(),
        ]);
        throw new \Exception('Invalid response status: ' . $response->getStatusCode());
      }

      $csv_content = $response->getBody()->getContents();
      return $this->parseCsv($csv_content);

    }
    catch (RequestException $e) {
      $this->logger->error('Error retrieving CSV from {url}: {message}', [
        'url' => $url,
        'message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parses a CSV file.
   *
   * @param string $csv_content
   *   The CSV content.
   *
   * @return array
   *   An array of CSV data.
   */
  private function parseCsv(string $csv_content): array {
    $rows = [];

    $temp_file_path = 'temporary://csv_import.csv';

    $file_uri = $this->fileSystem->saveData($csv_content, $temp_file_path, FileExists::Replace);

    if ($file_uri) {
      $handle = fopen($this->fileSystem->realpath($file_uri), 'r');

      if ($handle !== FALSE) {
        // Skip first header row.
        $first_row = TRUE;
        while (($data = fgetcsv($handle)) !== FALSE) {
          if ($first_row) {
            $first_row = FALSE;
            continue;
          }

          // Removes empty rows.
          if (empty($data[1]) || empty($data[3])) {
            continue;
          }

          if (isset($data[1])) {
            $data[1] = $this->convertDateToTimestamp(trim($data[1]));
          }

          if (isset($data[3])) {
            $data[3] = $this->convertMarkdownLinks(trim($data[3]));
          }

          $rows[] = $data;
        }
        fclose($handle);
      }
    }

    // Sort by date descending.
    usort($rows, function ($a, $b) {
      return $b[1] <=> $a[1];
    });

    // Convert dates.
    foreach ($rows as &$row) {
      $row[1] = $this->formatDate($row[1]);
    }

    return $rows;
  }

  /**
   * Converts dates from Google Sheets into formatted dates.
   *
   * @param string $value
   *   A string of text to convert into a formatted date.
   *
   * @return int
   *   A Unix timestamp.
   */
  private function convertDateToTimestamp(string $value): int {
    $value = str_replace("\u{A0}", ' ', $value);
    $date = \DateTime::createFromFormat('M d, Y', $value);

    return $date ? $date->getTimestamp() : 0;

  }

  /**
   * Converts a Unix timestamp into a Drupal formatted date.
   *
   * @param int $timestamp
   *   The Unix timestamp.
   *
   * @return string
   *   A formatted Drupal date.
   */
  private function formatDate(int $timestamp): string {
    return $this->dateFormatter->format($timestamp, 'medium');
  }

  /**
   * Convert markdown links into HTML links.
   *
   * @param string $text
   *   Text to covert from markdown into HTML.
   */
  private function convertMarkdownLinks(string $text): string {
    $markdown_link_regex = "/\[(.*?)\]\((https?:\/\/.*?)\)/";

    return preg_replace_callback($markdown_link_regex, function ($matches) {
      $converted_link = Link::fromTextAndUrl(
        $this->t('@link_text', ['@link_text' => $matches[1]]),
        Url::fromUri($matches[2])
      );

      return $converted_link->toString()->__toString();
    }, $text);

  }

  /**
   * Returns table headers. These are statically set.
   *
   * @return array
   *   An array of table headers.
   */
  public function getTableHeader(): array {

    $tableHeader = [
      [
        'data' => $this->t('Date'),
      ],
      [
        'data' => $this->t('Update'),
      ],
    ];

    return $tableHeader;
  }

  /**
   * Returns table rows based on the announcements found in csv.
   *
   * @return array
   *   An array of table rows with announcement data.
   */
  public function getTableRows(): array {
    $table_rows = [];

    if ($cache = $this->cache->get('hs_dashboard_csv_announcements')) {
      return $cache->data;
    }
    $csv_data = $this->getCsvAnnouncements(static::ANNOUNCEMENTS_CSV);

    foreach ($csv_data as $row) {
      $table_rows[] = [
        'data' => [
          ['data' => $row[1]],
          ['data' => ['#markup' => $row[3]]],
        ],
      ];
    }

    // Cache for 2 minutes.
    $this->cache->set('hs_dashboard_csv_announcements', $table_rows, time() + 120);
    return $table_rows;
  }

}
