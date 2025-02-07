<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Output\RenderedContentInterface;
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
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('hs_dashboard');
    $this->fileSystem = $file_system;
    $this->dateFormatter = $date_formatter;
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

          if (isset($data[1])) {
            $data[1] = $this->convertDate($data[1]);
          }

          if (isset($data[2])) {
            $data[2] = $this->convertMarkdown($data[2]);
          }

          if (isset($data[3])) {
            $data[3] = $this->convertMarkdown($data[3]);
          }

          $rows[] = $data;
        }
        fclose($handle);
      }
    }

    return $rows;
  }

  /**
   * Converts dates from Google Sheets into formatted dates.
   *
   * @param string $value
   *   A string of text to convert into a formatted date.
   *
   * @return string
   *   A formatted date or the original data if a date could not be created.
   */
  private function convertDate(string $value): string {
    $value = str_replace("\u{A0}", ' ', $value);
    $date = \DateTime::createFromFormat('M d, Y', $value);

    if ($date) {
      $timestamp = $date->getTimestamp();
      return $this->dateFormatter->format($timestamp, 'medium');
    }

    return $value;
  }

  /**
   * Convert markdown into HTML.
   *
   * @param string $text
   *   Text to covert from markdown into HTML.
   *
   * @return League\CommonMark\Output\RenderedContentInterface
   *   The rendered HTML output.
   */
  private function convertMarkdown(string $text): RenderedContentInterface {
    $converter = new CommonMarkConverter(
      [
        'html_input' => 'escape',
        'allow_unsafe_links' => FALSE,
      ]);
    return $converter->convert($text);
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
        'data' => $this->t('Title'),
      ],
      [
        'data' => $this->t('Description'),
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
    $tableRows = [];
    $csv_data = $this->getCsvAnnouncements(static::ANNOUNCEMENTS_CSV);

    foreach ($csv_data as $row) {
      $tableRows[] = [
        'data' => [
          ['data' => $row[1]],
          ['data' => ['#markup' => $row[2]]],
          ['data' => ['#markup' => $row[3]]],
        ],
      ];
    }

    return $tableRows;
  }

}
