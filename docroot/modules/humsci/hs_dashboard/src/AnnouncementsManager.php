<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to handle HDSP Announcements.
 */
class AnnouncementsManager implements ContainerInjectionInterface {

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
   * Constructs a new ViewsBasicManager object.
   *
   * @param GuzzleHttp\ClientInterface $http_client
   *   The guzzle http client.
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger interface.
   * @param Drupal\Core\File\FileSystemInterface $file_system
   *   The logger interface.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('hs_dashboard');
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('file_system'),
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

          $rows[] = $data;
        }
        fclose($handle);
      }
    }

    return $rows;
  }

  /**
   * Converts dates from Google Sheets into Unix timestamps.
   */
  private function convertDate(string $value): string|int {
    $value = str_replace("\u{A0}", ' ', $value);

    $date = \DateTime::createFromFormat('M d, Y', $value);

    if ($date) {
      return $date->getTimestamp(); // Convert to Unix timestamp
    }

    return $value; // Return original value if not a valid date
}

  /**
   * @todo Add method description.
   */
  public function getTableHeader(): array {
    $csv_data = $this->getCsvAnnouncements(static::ANNOUNCEMENTS_CSV);
    kint($csv_data);
    $tableHeader = [
      [
        'data' => 'Date',
      ],
      [
        'data' => 'Title',
      ],
      [
        'data' => 'Description',
      ],
    ];

    return $tableHeader;
  }

  /**
   * @todo Add method description.
   */
  public function getTableRows(): array {
    $tableRows = [
      [
        'data' => [
          [
            'data' => '01-30-2025 15:11:01',
          ],
          [
            'data' => 'A happy little stream',
          ],
          [
            'data' => 'Citizens of distant epochs worldlets ship of the imagination light years finite but unbounded, star stuff harvesting star light. The carbon in our apple pies, shores of the cosmic ocean brain is the seed of intelligence a very small stage in a vast cosmic arena of brilliant syntheses tendrils of gossamer clouds. A very small stage in a vast cosmic arena. Colonies. Evidence. Science and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
          ],
        ],
      ],
      [
        'data' => [
          [
            'data' => '01-30-2025 15:11:11',
          ],
          [
            'data' => 'White mazagran',
          ],
          [
            'data' => 'At grounds mocha single shot cup so kopi-luwak affogato coffee flavour. Flavour, id, caramelization, sit, flavour robusta ristretto frappuccino white mazagran. As saucer, americano, con panna cup cortado cappuccino sit espresso. Turkish, white, turkish steamed con panna doppio grinder grounds. Crema aroma decaffeinated whipped carajillo cinnamon to go.',
          ],
        ],
      ],
    ];

    return $tableRows;
  }

}
