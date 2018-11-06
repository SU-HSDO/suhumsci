<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
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
    $group_component = $this->getGroupComponent($section_storage, $delta, $group);
    $group_config = $group_component->get('configuration');

    $component = new SectionComponent($uuid, $group, $group_config['children'][$uuid]);
    $form_state->set('layout_builder__component', $component);

    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

  /**
   * Get the component for the given group within the section.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   Entity section storage object.
   * @param int $delta
   *   Section delta in the storage.
   * @param string $group_name
   *   Group machine name.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   Section component object for the group.
   */
  protected function getGroupComponent(SectionStorageInterface $section_storage, $delta, $group_name) {
    $section = $section_storage->getSection($delta);
    foreach ($section->getComponents() as $component) {
      $component_config = $component->get('configuration');
      list($component_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $component_config['id']);

      if ($component_id == 'group_block' && $component_config['machine_name'] == $group_name) {
        return $component;
      }
    }
  }

}
