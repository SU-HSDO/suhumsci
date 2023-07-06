<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the H&amp;S Entities type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hs_entities_type",
 *   label = @Translation("H&amp;S Entities type"),
 *   label_collection = @Translation("H&amp;S Entities types"),
 *   label_singular = @Translation("h&amp;s entities type"),
 *   label_plural = @Translation("h&amp;s entitiess types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count h&amp;s entitiess type",
 *     plural = "@count h&amp;s entitiess types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hs_entities\Form\HsEntitiesTypeForm",
 *       "edit" = "Drupal\hs_entities\Form\HsEntitiesTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hs_entities\HsEntitiesTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer h&amp;s entities types",
 *   bundle_of = "hs_entities",
 *   config_prefix = "hs_entities_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hs_entities_types/add",
 *     "edit-form" = "/admin/structure/hs_entities_types/manage/{hs_entities_type}",
 *     "delete-form" = "/admin/structure/hs_entities_types/manage/{hs_entities_type}/delete",
 *     "collection" = "/admin/structure/hs_entities_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HsEntitiesType extends ConfigEntityBundleBase {

  /**
   * The machine name of this h&amp;s entities type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the h&amp;s entities type.
   *
   * @var string
   */
  protected $label;

}
