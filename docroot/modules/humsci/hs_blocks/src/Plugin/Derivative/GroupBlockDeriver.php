<?php

namespace Drupal\hs_blocks\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 */
class GroupBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * GroupBlockDeriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_definition) {
    // Each entity type will have different context, so we have to provide
    // a derivative for each of those contexts.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if (!$entity_type->get('field_ui_base_route')) {
        continue;
      }
      $derivative = $base_definition;

      $derivative['admin_label'] = $this->t('Group Block: @type', ['@type' => $entity_type->getLabel()]);
      $derivative['category'] = $entity_type->getLabel();
      $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type->id())
        ->setLabel($entity_type->getLabel());
      $derivative['context'] = [
        'entity' => $context_definition,
      ];
      $this->derivatives[$entity_type->id()] = $derivative;
    }

    return $this->derivatives;
  }

}
