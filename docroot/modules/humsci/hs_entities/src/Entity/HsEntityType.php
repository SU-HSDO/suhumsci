<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the HumSci Entity type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hs_entity_type",
 *   label = @Translation("HumSci Entity type"),
 *   label_collection = @Translation("HumSci Entity types"),
 *   label_singular = @Translation("humsci entity type"),
 *   label_plural = @Translation("humsci entities types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count humsci entities type",
 *     plural = "@count humsci entities types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hs_entities\Form\HsEntityTypeForm",
 *       "edit" = "Drupal\hs_entities\Form\HsEntityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hs_entities\HsEntityTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer humsci entity types",
 *   bundle_of = "hs_entity",
 *   config_prefix = "hs_entity_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hs_entity_types/add",
 *     "edit-form" = "/admin/structure/hs_entity_types/manage/{hs_entity_type}",
 *     "delete-form" = "/admin/structure/hs_entity_types/manage/{hs_entity_type}/delete",
 *     "collection" = "/admin/structure/hs_entity_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HsEntityType extends ConfigEntityBundleBase {

  /**
   * The machine name of this humsci entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the humsci entity type.
   *
   * @var string
   */
  protected $label;

}
