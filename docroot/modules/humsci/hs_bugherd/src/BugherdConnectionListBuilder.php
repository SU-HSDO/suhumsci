<?php

namespace Drupal\hs_bugherd;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Bugherd Connection entities.
 */
class BugherdConnectionListBuilder extends ConfigEntityListBuilder {

  /**
   * Bugherd Api service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('hs_bugherd')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, HsBugherd $bugherd_api) {
    parent::__construct($entity_type, $storage);
    $this->bugherdApi = $bugherd_api;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Name'),
      'bugherd' => $this->t('Bugherd Project'),
      'jira' => $this->t('Jira Project'),
      'url' => $this->t('Url'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $project = $this->bugherdApi->getProject($entity->getBugherdProject());
    $row['bugherd'] = $project['name'];
    $row['jira'] = $entity->getJiraProject();
    $row['url'] = $project['devurl'];
    return $row + parent::buildRow($entity);
  }

}
