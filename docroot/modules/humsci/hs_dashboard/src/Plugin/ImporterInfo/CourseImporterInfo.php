<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\hs_dashboard\Plugin\ImporterInfoBase;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Course importer info.
 *
 * @ImporterInfo(
 *   id = "course_importer_info",
 *   label = @Translation("Course Importers"),
 *   description = @Translation("Retrieves event importer information from Localist."),
 *   weight = 10,
 * )
 */
class CourseImporterInfo extends ImporterInfoBase {

  use StringTranslationTrait;

  /**
   * Constructs a new CourseImporterInfo object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The KeyValue factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The DateFormatter.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration manager interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value_factory,
    DateFormatterInterface $date_formatter,
    MigrationPluginManagerInterface $migration_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $key_value_factory, $date_formatter, $migration_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.migration'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTableHeaders(): array {
    return [
      $this->t('Courses'),
      $this->t('Last Imported'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getTableRows(): array {
    $course_importer = $this->migrationManager->getDefinition('hs_courses');

    // This list of URLs is controlled at:
    // @url /admin/config/importers/courses-importer
    $urls = $course_importer['source']['urls'] ?? [];

    $table_rows = [];

    foreach ($urls as $url) {
      $table_rows[] = [
        'data' => [
          ['data' => $this->buildCourseLink($url)],
          ['data' => $this->lastImportTime('hs_courses')],
        ],
      ];
    }
    return $table_rows;
  }

  /**
   * {@inheritDoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    return $this->t('There are no Stanford Courses importers configured.');
  }

  /**
   * Create a course link to display in the dashboard.
   *
   * @param string $url
   *   The https://explorecourses.stanford.edu XML feed that we're importing.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A renderable course link.
   */
  protected function buildCourseLink(string $url): GeneratedLink {
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $params);
    $text = $params['q'] ?? 'unknown';
    // Oddly, there are some non-printable characters in many of these URLs.
    $text = preg_replace('/[^[:print:]]/', '', $text);
    // There is a query param that controls the output format.  Something like:
    // `view=xml-20200810`
    // We just need to remove the 'xml-' portion.
    $human_url = str_replace('view=xml-', 'view=', $url);
    return Link::fromTextAndUrl($text, Url::fromUri($human_url))->toString();
  }

}
