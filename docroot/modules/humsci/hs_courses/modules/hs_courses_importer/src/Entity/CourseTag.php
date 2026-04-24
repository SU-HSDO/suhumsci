<?php

namespace Drupal\hs_courses_importer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Course Tag Translation entity.
 */
#[ConfigEntityType(
  id: "hs_course_tag",
  label: new TranslatableMarkup("Course Tag Translation"),
  handlers: [
    "view_builder" => "Drupal\Core\Entity\EntityViewBuilder",
    "list_builder" => "Drupal\hs_courses_importer\CourseTagListBuilder",
    "form" => [
      "add" => "Drupal\hs_courses_importer\Form\CourseTagForm",
      "edit" => "Drupal\hs_courses_importer\Form\CourseTagForm",
      "delete" => "Drupal\hs_courses_importer\Form\CourseTagDeleteForm"
    ],
    "route_provider" => [
      "html" => "Drupal\hs_courses_importer\CourseTagHtmlRouteProvider",
    ],
  ],
  config_prefix: "hs_course_tag",
  admin_permission: "administer site configuration",
  entity_keys: [
    "id" => "id",
    "label" => "label",
    "uuid" => "uuid"
  ],
  links: [
    "canonical" => "/admin/config/importers/course-tag/{hs_course_tag}",
    "add-form" => "/admin/config/importers/course-tag/add",
    "edit-form" => "/admin/config/importers/course-tag/{hs_course_tag}/edit",
    "delete-form" => "/admin/config/importers/course-tag/{hs_course_tag}/delete",
    "collection" => "/admin/config/importers/course-tag"
  ],
  config_export: [
    "id",
    "label",
    "tag"
  ],
)]
class CourseTag extends ConfigEntityBase implements CourseTagInterface {

  /**
   * The Course Tag Translation ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The course tag from explorecourses.stanford.edu.
   *
   * @var string
   */
  protected $label;

  /**
   * The translated text.
   *
   * @var string
   */
  protected $tag;

  /**
   * {@inheritdoc}
   */
  public function tag() {
    return $this->tag;
  }

}
