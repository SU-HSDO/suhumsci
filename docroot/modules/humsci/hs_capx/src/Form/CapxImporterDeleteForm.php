<?php

namespace Drupal\hs_capx\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Capx importer entities.
 */
class CapxImporterDeleteForm extends EntityConfirmFormBase {

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
    $this->entity->delete();
    $this->messenger()->addMessage($this->t('content @type: deleted @label.',
      [
        '@type' => $this->entity->bundle(),
        '@label' => $this->entity->label(),
      ]
    ));
    $form_state->setRedirectUrl($this->getCancelUrl());
    Cache::invalidateTags(['migration_plugins', 'hs_capx_config']);
  }

}
