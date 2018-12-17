<?php

namespace Drupal\hs_shortcut_nesting\Form;

use Drupal\Core\Form\FormStateInterface;
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

    $form['shortcuts']['links']['#tabledrag'] = $this->getTableDragSettings();
    foreach ($this->entity->getShortcuts() as $shortcut) {
      $indentation = [];

      $depth = (int) $shortcut->get('depth')->getString();
      if ($depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $depth,
        ];
      }

      $first_column = &$form['shortcuts']['links'][$shortcut->id()]['name'];
      $first_column['#title'] = $shortcut->getTitle() . ': ' . $shortcut->id();

      $first_column['#prefix'] = !empty($indentation) ? \Drupal::service('renderer')
        ->render($indentation) : '';

      $first_column['shortcut_id'] = [
        '#type' => 'hidden',
        '#value' => $shortcut->id(),
        '#attributes' => ['class' => ['shortcut-id']],
      ];
      $first_column['parent'] = [
        '#type' => 'hidden',
        '#default_value' => $shortcut->get('parent')->getString() ?: 0,
        '#attributes' => ['class' => ['shortcut-parent']],
      ];
      $first_column['depth'] = [
        '#type' => 'hidden',
        '#default_value' => $depth,
        '#attributes' => ['class' => ['shortcut-depth']],
      ];
    }
    return $form;
  }

  /**
   * Get the table drag settings array.
   *
   * @return array
   *   Table drag settings.
   */
  protected function getTableDragSettings() {
    return [
      [
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'shortcut-parent',
        'subgroup' => 'shortcut-parent',
        'source' => 'shortcut-id',
        "hidden" => FALSE,
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
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $link_values = $form_state->getValue(['shortcuts', 'links']);
    $link_values = $this->sortLinkWeights($link_values);

    foreach ($this->entity->getShortcuts() as $shortcut) {
      $shortcut_values = $link_values[$shortcut->id()];
      $shortcut->set('parent', $shortcut_values['name']['parent']);
      $shortcut->setWeight($shortcut_values['weight']);
      $shortcut->set('depth', $shortcut_values['name']['depth']);
      $shortcut->save();
    }
    $this->messenger()
      ->addStatus($this->t('The shortcut set has been updated.'));
  }

  /**
   * Sort the submitted link values and give them appropriate weights.
   *
   * @param array $values
   *   Form state values.
   *
   * @return array
   *   Modified values with new weights.
   */
  protected function sortLinkWeights(array $values) {
    $sortable_array = [];
    foreach (array_keys($values) as $link_id) {
      $weight = $this->getRootWeight($values, $link_id);

      while (in_array($weight, $sortable_array)) {
        $weight += .001;
      }

      $sortable_array[$link_id] = $weight;
    }
    asort($sortable_array, SORT_NUMERIC);

    foreach (array_keys($sortable_array) as $new_weight => $link_id) {
      $values[$link_id]['weight'] = $new_weight;
    }
    return $values;
  }

  /**
   * Get the root parent weight of the provided link from the form.
   *
   * @param array $values
   *   Form submitted values.
   * @param int $link_id
   *   Shortcut Id.
   *
   * @return int
   *   Root parent weight.
   */
  protected function getRootWeight(array $values, $link_id) {
    if ($values[$link_id]['name']['parent']) {
      return $this->getRootWeight($values, $values[$link_id]['name']['parent']);
    }
    return $values[$link_id]['weight'];
  }

}
