<?php

namespace Drupal\react_paragraphs\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field_permissions\FieldPermissionsServiceInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get list of available entities.
 *
 * @RestResource(
 *   id = "react_paragraphs_field_access",
 *   label = @Translation("Field Access"),
 *   uri_paths = {
 *     "canonical" = "/api/field-access/{field_config}"
 *   }
 * )
 */
class ReactParagraphsFieldAccess extends ResourceBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Field permission service.
   *
   * @var \Drupal\field_permissions\FieldPermissionsServiceInterface
   */
  protected $fieldPermissions;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentAccount;

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
      $container->get('entity_type.manager'),
      $container->get('field_permissions.permissions_service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, FieldPermissionsServiceInterface $field_permissions, AccountProxyInterface $current_account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldPermissions = $field_permissions;
    $this->currentAccount = $current_account;
  }

  /**
   * {@inheritdoc}
   */
  public function get($field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager->getStorage('field_config')
      ->load($field_config);
    if (!$field) {
      return new Response('Field doesnt exist', 404);
    }

    list($entity_type, $entity_bundle, $field_name) = explode('.', $field_config);
    $entity_defintion = $this->entityTypeManager->getDefinition($entity_type);
    $bundle_key = $entity_defintion->getKey('bundle');

    $temp_entity = $this->entityTypeManager->getStorage($entity_type)
      ->create([$bundle_key => $entity_bundle]);
    $field_items = $temp_entity->get($field_name);
    if ($this->fieldPermissions->getFieldAccess('edit', $field_items, $this->currentAccount, $field)) {
      return new JsonResponse(TRUE);
    }

    return new Response('Access Denied', 401);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [];
  }

}
