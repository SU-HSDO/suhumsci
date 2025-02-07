<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder_component_attributes\Form\ManageComponentAttributesForm;

/**
 * Class HsManageBlockAttributesForm.
 *
 * @package Drupal\hs_blocks\Form
 */
class HsManageBlockAttributesForm extends ManageComponentAttributesForm {

  /**
   * Parent group uuid.
   *
   * @var string
   */
  protected $group;

  /**
   * Parent group section delta.
   *
   * @var int
   */
  protected $groupDelta;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'hs_blocks_manage_block_attributes';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $group = NULL, $uuid = NULL) {
    $this->group = $group;
    $this->groupDelta = $delta;

    $parent_component = $section_storage->getSection($delta)
      ->getComponent($group);

    $parent_config = $parent_component->get('configuration');
    $item_config = $parent_config['children'][$uuid];

    $component = new SectionComponent($uuid, 'content', $item_config);
    foreach ($item_config['additional'] as $key => $value) {
      $component->set($key, $value);
    }

    $section = new Section('layout_onecol');
    $section->appendComponent($component);
    $section_storage->appendSection($section);

    $section_deltas = array_keys($section_storage->getSections());

    return parent::buildForm($form, $form_state, $section_storage, end($section_deltas), $uuid);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $section_deltas = array_keys($this->sectionStorage->getSections());
    $removing_section_delta = end($section_deltas);
    $component_attributes = $this->sectionStorage->getSection($removing_section_delta)
      ->getComponent($this->uuid)->get('component_attributes');
    $this->sectionStorage->removeSection($removing_section_delta);

    $group = $this->sectionStorage->getSection($this->groupDelta)
      ->getComponent($this->group);
    $group_config = $group->get('configuration');
    $group_config['children'][$this->uuid]['additional']['component_attributes'] = $component_attributes;
    $group->setConfiguration($group_config);

    $this->layoutTempstore->set($this->sectionStorage);
  }

}
