<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class HsAddBlockForm extends HsConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_blocks_add_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Add Block');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $plugin_id = NULL) {
    // Only generate a new component once per form submission.
    if (!$component = $form_state->get('layout_builder__component')) {
      // This is the new component that will get saved to the group block as a
      // child.
      $component = new SectionComponent($this->uuidGenerator->generate(), $group, ['id' => $plugin_id]);
      $form_state->set('layout_builder__component', $component);
    }
    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

}
