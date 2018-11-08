<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
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
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $uuid = NULL) {
    $this->group = $group;
    $this->uuid = $uuid;
    // For some reason the uuid property doesn't carry through to the method
    // handlSectionStorage, so we just set it in the form state for easy access.
    $form_state->set('hs_blocks_uuid', $uuid);
    return parent::buildForm($form, $form_state, $section_storage, $delta);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleSectionStorage(SectionStorageInterface $section_storage, FormStateInterface $form_state) {
    /** @var \Drupal\layout_builder\Section $section */
    foreach ($section_storage->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $component_config = $component->get('configuration');
        list($component_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $component_config['id']);

        // Find the correct group block and remove the child.
        if ($component_id == 'group_block' && isset($component_config['machine_name']) && $component_config['machine_name'] == $this->group) {
          unset($component_config['children'][$form_state->get('hs_blocks_uuid')]);
          $component->setConfiguration($component_config);
        }
      }
    }
  }

}
