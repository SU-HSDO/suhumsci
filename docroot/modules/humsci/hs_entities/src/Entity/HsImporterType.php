<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the HumSci Importer type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hs_importer_type",
 *   label = @Translation("HumSci Importer type"),
 *   label_collection = @Translation("HumSci Importer types"),
 *   label_singular = @Translation("humsci importer type"),
 *   label_plural = @Translation("humsci importers types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count humsci importers type",
 *     plural = "@count humsci importers types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hs_entities\Form\HsImporterTypeForm",
 *       "edit" = "Drupal\hs_entities\Form\HsImporterTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hs_entities\HsImporterTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer humsci importer types",
 *   bundle_of = "hs_importer",
 *   config_prefix = "hs_importer_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hs_importer_types/add",
 *     "edit-form" = "/admin/structure/hs_importer_types/manage/{hs_importer_type}",
 *     "delete-form" = "/admin/structure/hs_importer_types/manage/{hs_importer_type}/delete",
 *     "collection" = "/admin/structure/hs_importer_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HsImporterType extends ConfigEntityBundleBase {

  /**
   * The machine name of this humsci importer type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the humsci importer type.
   *
   * @var string
   */
  protected $label;

}
