<?php

namespace Drupal\hs_courses_importer\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TanslateCourseTag' migrate process plugin.
 *
 * Example usage:
 *
 * @code
 * process:
 *   field_tags:
 *     plugin: translate_course_tag
 *     source: tags
 *     ignore_empty: true
 * @endcode
 */
#[MigrateProcess(
  id: "translate_course_tag",
)]
class TranslateCourseTag extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $tag_storage = $this->entityTypeManager->getStorage('hs_course_tag');
    /** @var \Drupal\hs_courses_importer\Entity\CourseTagInterface $tag_entity */
    foreach ($tag_storage->loadMultiple() as $tag_entity) {
      if ($value == $tag_entity->label()) {
        return $tag_entity->tag();
      }
    }

    // A translation entity was not found, and we want to ignore the value if
    // no translation was found.
    if (isset($this->configuration['ignore_empty']) && $this->configuration['ignore_empty']) {
      return NULL;
    }
    return $value;
  }

}
