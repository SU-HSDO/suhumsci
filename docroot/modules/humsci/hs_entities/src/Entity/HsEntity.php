<?php

namespace Drupal\hs_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\hs_entities\HsEntityInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the HumSci entity entity class.
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
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
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

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the HumSci entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the HumSci entity was last edited.'));

    return $fields;
  }

}
