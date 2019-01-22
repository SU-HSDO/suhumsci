<?php

namespace Drupal\hs_capx;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Capx importer entities.
 */
class CapxImporterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Capx importer');
    $header['organization'] = $this->t('Organizations');
    $header['workgroups'] = $this->t('Workgroups');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['organization'] = $entity->getOrganizations(TRUE);
    $row['workgroups'] = $entity->getWorkgroups(TRUE);
    return $row + parent::buildRow($entity);
  }

}
