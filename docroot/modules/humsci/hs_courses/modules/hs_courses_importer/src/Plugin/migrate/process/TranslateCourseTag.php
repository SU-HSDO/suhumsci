<?php

namespace Drupal\hs_courses_importer\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TanslateCourseTag' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "translate_course_tag"
 * )
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
class TranslateCourseTag extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Storage Service for hs_course_tag entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tagTranslation;

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
    $this->tagTranslation = $entity_type_manager->getStorage('hs_course_tag');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /** @var \Drupal\hs_courses_importer\Entity\CourseTagInterface $tag_entity */
    foreach ($this->tagTranslation->loadMultiple() as $tag_entity) {
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
