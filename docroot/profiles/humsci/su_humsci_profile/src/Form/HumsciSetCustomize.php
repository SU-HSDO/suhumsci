<?php

namespace Drupal\su_humsci_profile\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\shortcut\Form\SetCustomize;

/**
 * Class HumsciSetCustomize.
 *
 * @package Drupal\su_humsci_profile\Form
 */
class HumsciSetCustomize extends SetCustomize {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    dpm($this->entity->getShortcuts());

    $form['shortcuts']['links']['#tabledrag'] = [
      [
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'shortcut-parent',
        'subgroup' => 'shortcut-parent',
        'source' => 'shortcut-id',
        "hidden" => false
      ],
      [
        'action' => 'depth',
        'relationship' => 'group',
        'group' => 'shortcut-depth',
        'hidden' => FALSE,
      ],
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'shortcut-weight',
      ],
    ];

    foreach (Element::children($form['shortcuts']['links']) as $link_id) {
      $first_column = &$form['shortcuts']['links'][$link_id]['name'];
      $first_column['shortcut_id'] = [
        '#type' => 'hidden',
        '#value' => $link_id,
        '#attributes' => ['class' => ['shortcut-id']],
      ];
      $first_column['parent'] = [
        '#type' => 'hidden',
        '#default_value' => 0,
        '#attributes' => ['class' => ['shortcut-parent']],
      ];
      $first_column['depth'] = [
        '#type' => 'hidden',
        '#default_value' => 0,
        '#attributes' => ['class' => ['shortcut-depth']],
      ];
    }
    return $form;
  }

  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    dpm($form_state->getValue(['shortcuts', 'links']));
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    dpm($entity);
  }

}
