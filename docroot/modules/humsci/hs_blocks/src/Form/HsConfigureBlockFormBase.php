<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\Form\ConfigureBlockFormBase;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Class HsConfigureBlockFormBase.
 *
 * @package Drupal\hs_blocks\Form
 */
abstract class HsConfigureBlockFormBase extends ConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, SectionComponent $component = NULL) {
    $form = parent::doBuildForm($form, $form_state, $section_storage, $delta, $component);
    $component_config = $component->get('configuration');
    // We only want to hide the label display checkbox for fields, not regular
    // blocks.
    if (strpos($component_config['id'], 'field_block') !== FALSE) {
      $form['settings']['label_display']['#type'] = 'hidden';
      $form['settings']['label_display']['#default_value'] = FALSE;
    }

    return $form;
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
    if (!$configuration || !$this->uuid) {
      return;
    }

    // The "Region" is the same as the machine name of the group. It's a
    // pseudo-region.
    /** @var \Drupal\layout_builder\SectionComponent $group_block */
    $group_block_name = $form_state->get('layout_builder__component')
      ->getRegion();

    $section = $this->sectionStorage->getSection($this->delta);
    $component = $section->getComponent($group_block_name);
    $component_config = $component->get('configuration');

    $configuration['context_mapping'] = $this->block->getContextMapping();
    $component_config['children'][$this->uuid] = $configuration;
    $component_config['children'] = array_filter($component_config['children']);

    // Save the new child into the group component.
    $component->setConfiguration($component_config);

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

}
