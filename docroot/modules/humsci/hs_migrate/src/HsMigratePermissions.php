<?php

namespace Drupal\hs_migrate;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HsMigratePermissions implements ContainerInjectionInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function permissions() {
    $permissions = [];

//    foreach ($this->entityTypeManager->getStorage('taxonomy_vocabulary')
//               ->loadMultiple() as $vocabulary) {
//      $permissions += [
//        'define view for vocabulary ' . $vocabulary->id() => [
//          'title' => $this->t('Define the view override for the vocabulary %vocabulary', ['%vocabulary' => $vocabulary->label()]),
//        ],
//      ];
//
//      $permissions += [
//        'define view for terms in ' . $vocabulary->id() => [
//          'title' => $this->t('Define the view override for terms in %vocabulary', ['%vocabulary' => $vocabulary->label()]),
//        ],
//      ];
//    }

    return $permissions;
  }

}
