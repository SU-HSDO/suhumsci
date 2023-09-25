<?php

namespace Drupal\hs_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the humsci importer entity edit forms.
 */
class HsImporterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New humsci importer %label has been created.', $message_arguments));
        $this->logger('hs_entities')->notice('Created new humsci importer %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The humsci importer %label has been updated.', $message_arguments));
        $this->logger('hs_entities')->notice('Updated humsci importer %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.hs_importer.collection');

    return $result;
  }

}
