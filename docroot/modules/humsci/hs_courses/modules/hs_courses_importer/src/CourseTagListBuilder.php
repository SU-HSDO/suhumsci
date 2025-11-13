<?php

namespace Drupal\hs_courses_importer;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hs_courses_importer\Entity\CourseTagInterface;

/**
 * Provides a listing of Course Tag Translation entities.
 */
class CourseTagListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Explore Courses Tag');
    $header['tag'] = $this->t('Translated Tag');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['tag'] = '';
    if ($entity instanceof CourseTagInterface) {
      $row['tag'] = $entity->tag();
    }
    return $row + parent::buildRow($entity);
  }

}
