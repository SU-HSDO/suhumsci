<?php

namespace Drupal\hs_layouts\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\Form\AddBlockForm;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class HsAddBlockForm extends AddBlockForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_layouts_add_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $plugin_id = NULL) {
    // Only generate a new component once per form submission.
    if (!$component = $form_state->get('layout_builder__component')) {
      $component = new SectionComponent($this->uuidGenerator->generate(), $group, ['id' => $plugin_id]);
      $section_storage->getSection($delta)->appendComponent($component);
      $form_state->set('layout_builder__component', $component);
    }
    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the plugin submit handler.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getPluginForm($this->block)
      ->submitConfigurationForm($form, $subform_state);

    // If this block is context-aware, set the context mapping.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($subform_state->getValue('context_mapping', []));
    }

    $configuration = $this->block->getConfiguration();

    /** @var SectionComponent $group_block */
    $group_block_name = $form_state->get('layout_builder__component')
      ->getRegion();

    $section = $this->sectionStorage->getSection($this->delta);
    foreach ($section->getComponents() as $component) {
      $component_config = $component->get('configuration');
      if ($component_config['id'] == 'group_block' && $component_config['machine_name'] == $group_block_name) {
        $configuration['context_mapping'] = $this->block->getContextMapping();
        $component_config['#children'][$this->uuid] = $configuration;
        $component->setConfiguration($component_config);
      }
    }
    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

}
