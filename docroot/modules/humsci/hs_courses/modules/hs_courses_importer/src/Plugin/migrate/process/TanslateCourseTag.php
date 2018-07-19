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
 *  id = "tanslate_course_tag"
 * )
 */
class TanslateCourseTag extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tagTranslationStorage;

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
    $this->tagTranslationStorage = $entity_type_manager->getStorage('hs_course_tag');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /** @var \Drupal\hs_courses_importer\Entity\CourseTagInterface $tag_entity */
    foreach ($this->tagTranslationStorage->loadMultiple() as $tag_entity) {
      if ($value == $tag_entity->label()) {
        return $tag_entity->tag();
      }
    }
    return $value;
  }

}
