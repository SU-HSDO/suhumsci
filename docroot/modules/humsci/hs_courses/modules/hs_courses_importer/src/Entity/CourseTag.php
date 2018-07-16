<?php

namespace Drupal\hs_courses_importer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Course Tag Translation entity.
 *
 * @ConfigEntityType(
 *   id = "hs_course_tag",
 *   label = @Translation("Course Tag Translation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hs_courses_importer\CourseTagListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hs_courses_importer\Form\CourseTagForm",
 *       "edit" = "Drupal\hs_courses_importer\Form\CourseTagForm",
 *       "delete" = "Drupal\hs_courses_importer\Form\CourseTagDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hs_courses_importer\CourseTagHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "hs_course_tag",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/migrate/hs_course_tag/{hs_course_tag}",
 *     "add-form" = "/admin/structure/migrate/hs_course_tag/add",
 *     "edit-form" = "/admin/structure/migrate/hs_course_tag/{hs_course_tag}/edit",
 *     "delete-form" = "/admin/structure/migrate/hs_course_tag/{hs_course_tag}/delete",
 *     "collection" = "/admin/structure/migrate/hs_course_tag"
 *   }
 * )
 */
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
   * Get the tranlated tag text.
   *
   * @return string
   */
  public function tag() {
    return $this->tag;
  }

}
