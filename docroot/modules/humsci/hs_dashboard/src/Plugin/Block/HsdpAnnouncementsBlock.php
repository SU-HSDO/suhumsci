<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hs_dashboard\AnnouncementsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an HSDP announcements block.
 *
 * @Block(
 *   id = "hs_dashboard_hsdp_announcements",
 *   admin_label = @Translation("HSDP Announcements"),
 *   category = @Translation("H&amp;S Blocks"),
 * )
 */
class HsdpAnnouncementsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The announcements manager.
   *
   * @var \Drupal\hs_dashboard\AnnouncementsManager
   */
  protected $announcementsManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new InlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\hs_dashboard\AnnouncementsManager $announcements_manager
   *   The announcements manager.
   * @param Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AnnouncementsManager $announcements_manager, DateFormatter $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->announcementsManager = $announcements_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hs_dashboard.announcements_manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // 5 minutes in seconds
    return 300;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $csv_data = $this->announcementsManager->getCsvAnnouncements();

    if (empty($csv_data)) {
      $build['#theme'] = 'markup';
      $build['#markup'] = $this->t('There were no announcements found.');
    }
    else {
      $build['content']['#theme'] = 'table';
      $build['content']['#header'] = $this->getTableHeader();
      $build['content']['#rows'] = $this->getTableRows($csv_data);
    }

    return $build;
  }

  /**
   * Returns table headers. These are statically set.
   *
   * @return array
   *   An array of table headers.
   */
  private function getTableHeader(): array {

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
   * Build and returns table rows from CSV data.
   *
   * @param array $csv_data
   *   The CSV data.
   *
   * @return array
   *   An array of table rows with announcement data.
   */
  private function getTableRows($csv_data): array {
    foreach ($csv_data as $row) {
      $formatted_date = $this->dateFormatter->format($row[1], 'humsci_with_year');
      $table_rows = [];
      $table_rows[] = [
        'data' => [
          ['data' => $formatted_date],
          ['data' => ['#markup' => $row[3]]],
        ],
      ];
    }

    return $table_rows;
  }

}
