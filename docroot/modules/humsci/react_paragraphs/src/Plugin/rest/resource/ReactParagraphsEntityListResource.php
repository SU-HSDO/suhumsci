<?php

namespace Drupal\react_paragraphs\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get list of available entities.
 *
 * @RestResource(
 *   id = "react_paragraphs_entity_list",
 *   label = @Translation("Entity List"),
 *   uri_paths = {
 *     "canonical" = "/entity-list/{entity_type}"
 *   }
 * )
 */
class ReactParagraphsEntityListResource extends ResourceBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_type) {
    $list = [];
    if ($this->entityTypeManager->hasDefinition($entity_type)) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      foreach ($storage->loadMultiple() as $entity) {
        $list[$entity->id()] = $entity->label();
      }
    }
    return new JsonResponse($list);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [];
  }

}
