<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\hs_entities\HsImporterInterface;

/**
 * Defines the humsci importer entity class.
 *
 * @ContentEntityType(
 *   id = "hs_importer",
 *   label = @Translation("HumSci Importer"),
 *   label_collection = @Translation("HumSci Importers"),
 *   label_singular = @Translation("humsci importer"),
 *   label_plural = @Translation("humsci importers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count humsci importers",
 *     plural = "@count humsci importers",
 *   ),
 *   bundle_label = @Translation("HumSci Importer type"),
 *   handlers = {
 *     "list_builder" = "Drupal\hs_entities\HsImporterListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\hs_entities\HsImporterAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hs_entities\Form\HsImporterForm",
 *       "edit" = "Drupal\hs_entities\Form\HsImporterForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hs_entities\Routing\HsImporterHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "hs_importer",
 *   admin_permission = "administer hs importer types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/hs-importer",
 *     "add-form" = "/hs-importer/add/{hs_importer_type}",
 *     "add-page" = "/hs-importer/add",
 *     "canonical" = "/hs-importer/{hs_importer}",
 *     "edit-form" = "/hs-importer/{hs_importer}",
 *     "delete-form" = "/hs-importer/{hs_importer}/delete",
 *   },
 *   bundle_entity_type = "hs_importer_type",
 *   field_ui_base_route = "entity.hs_importer_type.edit_form",
 * )
 */
class HsImporter extends ContentEntityBase implements HsImporterInterface {

}
