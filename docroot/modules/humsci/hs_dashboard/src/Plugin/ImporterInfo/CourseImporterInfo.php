<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\hs_dashboard\Plugin\ImporterInfoBase;
use Drupal\hs_dashboard\Plugin\ImporterInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Course importer info.
 *
 * @ImporterInfo(
 *   id = "course_importer_info",
 *   label = @Translation("Course Importers"),
 *   description = @Translation("Retrieves event importer information from Localist."),
 * )
 */
class CourseImporterInfo extends ImporterInfoBase implements ImporterInfoInterface, ContainerFactoryPluginInterface {

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTableHeaders(): array {
    return [
      $this->t('Course tag'),
      $this->t('Catalog'),
      $this->t('XML data'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getTableRows(): array {
    /** @var \Drupal\hs_courses_importer\Entity\CourseTagInterface[] $tags */
    $tags = $this->entityTypeManager->getStorage('hs_course_tag')->loadMultiple();
    $table_rows = [];
    foreach ($tags as $tag) {
      $table_rows[] = [
        'data' => [
          ['data' => $tag->label()],
          ['data' => $this->buildCourseLink($this->t('Explore courses'), 'catalog', $tag->label())],
          ['data' => $this->buildCourseLink($this->t('XML'), 'xml-20200810', $tag->label())],
        ],
      ];
    }
    return $table_rows;
  }

  /**
   * {@inheritDoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    return $this->t('<em>There are no course importers configured.</em>');
  }

  /**
   * Create a course link to display in the dashboard.
   *
   * @param string $text
   *   The link text to display.
   * @param string $format
   *   The display format as 'catalog' or 'xml-20200810'.
   * @param string $course_tag
   *   The course tag.
   *
   * @return string
   *   A renderable course link.
   */
  protected function buildCourseLink($text, $format, $course_tag) {
    $tag = htmlspecialchars($course_tag, ENT_QUOTES, 'UTF-8');
    $uri = "https://explorecourses.stanford.edu/search?view={$format}&filter-coursestatus-Active=on&page=0&catalog=&academicYear=&q={$tag}&collapse=";
    return Link::fromTextAndUrl($text, Url::fromUri($uri))->toString();
  }

}
