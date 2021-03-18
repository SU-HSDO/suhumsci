<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to update a block.
 *
 * @internal
 */
class HsUpdateBlockForm extends HsConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_blocks_update_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Update');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $uuid = NULL) {
    $section = $section_storage->getSection($delta);
    $group_component = $section->getComponent($group);
    $group_config = $group_component->get('configuration');

    $component = new SectionComponent($uuid, $group, $group_config['children'][$uuid]);
    $form_state->set('layout_builder__component', $component);

    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

}
