<?php

namespace Drupal\hs_capx\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Capx importer entities.
 */
class CapxImporterDeleteForm extends EntityConfirmFormBase {

  /**
   * Migration cache bin service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $migrationCache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cache.discovery_migration'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $migration_cache) {
    $this->migrationCache = $migration_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.capx_importer.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->migrationCache->invalidate('migration_plugins');
    Cache::invalidateTags(['migration_plugins', 'hs_capx_config']);

    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
