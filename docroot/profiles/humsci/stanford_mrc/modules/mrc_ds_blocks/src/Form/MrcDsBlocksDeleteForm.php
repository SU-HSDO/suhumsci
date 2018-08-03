<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_ds_blocks\MrcDsBlocks;

/**
 * Provides a form for removing a block from a bundle.
 */
class MrcDsBlocksDeleteForm extends ConfirmFormBase {

  /**
   * The block to delete.
   *
   * @var \stdClass
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mrc_ds_blocks_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $mrc_ds_blocks_id = NULL, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $mode = $this->getRequest()->attributes->get('form_mode_name');
    }
    else {
      $mode = $this->getRequest()->attributes->get('view_mode_name');
    }

    if (empty($mode)) {
      $mode = 'default';
    }

    $this->block = mrc_ds_blocks_load_block($mrc_ds_blocks_id, $entity_type_id, $bundle, $context, $mode);
    $this->block->blockId = $mrc_ds_blocks_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$this->block->entity_type][$this->block->bundle]['label'];

    mrc_ds_blocks_delete_block($this->block);

    drupal_set_message(t('The block %block_id has been deleted from the %type content type.', [
      '%block_id' => t($this->block->blockId),
      '%type' => $bundle_label,
    ]));

    // Redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block %block?', ['%block' => t($this->block->blockId)]);
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
  public function getCancelUrl() {
    return MrcDsBlocks::getFieldUiRoute($this->block);
  }

}
