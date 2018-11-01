<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\Form\ConfigureBlockFormBase;

/**
 * Class HsConfigureBlockFormBase.
 *
 * @package Drupal\hs_blocks\Form
 */
abstract class HsConfigureBlockFormBase extends ConfigureBlockFormBase {

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

    /** @var \Drupal\layout_builder\SectionComponent $group_block */
    $group_block_name = $form_state->get('layout_builder__component')
      ->getRegion();

    $section = $this->sectionStorage->getSection($this->delta);

    foreach ($section->getComponents() as $component) {

      $component_config = $component->get('configuration');
      list($component_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $component_config['id']);

      if ($component_id == 'group_block' && $component_config['machine_name'] == $group_block_name) {
        $configuration['context_mapping'] = $this->block->getContextMapping();
        $component_config['#children'][$this->uuid] = $configuration;
        $component->setConfiguration($component_config);
      }
    }

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

}
