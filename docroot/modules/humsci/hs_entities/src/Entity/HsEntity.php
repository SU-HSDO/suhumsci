<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\hs_entities\HsEntityInterface;

/**
 * Defines the humsci entity entity class.
 *
 * @ContentEntityType(
 *   id = "hs_entity",
 *   label = @Translation("HumSci Entity"),
 *   label_collection = @Translation("HumSci Entities"),
 *   label_singular = @Translation("humsci entity"),
 *   label_plural = @Translation("humsci entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count humsci entities",
 *     plural = "@count humsci entities",
 *   ),
 *   bundle_label = @Translation("HumSci Entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\hs_entities\HsEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\hs_entities\HsEntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hs_entities\Form\HsEntityForm",
 *       "edit" = "Drupal\hs_entities\Form\HsEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hs_entities\Routing\HsEntityHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "hs_entity",
 *   admin_permission = "administer hs entity types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/hs-entity",
 *     "add-form" = "/hs-entity/add/{hs_entity_type}",
 *     "add-page" = "/hs-entity/add",
 *     "canonical" = "/hs-entity/{hs_entity}",
 *     "edit-form" = "/hs-entity/{hs_entity}",
 *     "delete-form" = "/hs-entity/{hs_entity}/delete",
 *   },
 *   bundle_entity_type = "hs_entity_type",
 *   field_ui_base_route = "entity.hs_entity_type.edit_form",
 * )
 */
class HsEntity extends ContentEntityBase implements HsEntityInterface {

}
