<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Form\RemoveBlockForm;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to confirm the removal of a block.
 *
 * @internal
 */
class HsRemoveBlockForm extends RemoveBlockForm {

  /**
   * The current region.
   *
   * @var string
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_blocks_remove_block';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $section = $this->sectionStorage->getSection($this->delta);
    $component = $section->getComponent($this->group);
    $component_config = $component->get('configuration');
    $label = $component_config['children'][$this->uuid]['label'];
    return $this->t('Are you sure you want to remove the %label block?', ['%label' => $label]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $uuid = NULL) {
    $this->group = $group;
    $this->uuid = $uuid;
    // For some reason the uuid property doesn't carry through to the method
    // handlSectionStorage, so we just set it in the form state for easy access.
    $form_state->set('hs_blocks_uuid', $uuid);
    return parent::buildForm($form, $form_state, $section_storage, $delta, $group, $uuid);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleSectionStorage(SectionStorageInterface $section_storage, FormStateInterface $form_state) {
    $section = $section_storage->getSection($this->delta);
    $component = $section->getComponent($this->group);
    $component_config = $component->get('configuration');
    unset($component_config['children'][$form_state->get('hs_blocks_uuid')]);
    $component->setConfiguration($component_config);
  }

}
