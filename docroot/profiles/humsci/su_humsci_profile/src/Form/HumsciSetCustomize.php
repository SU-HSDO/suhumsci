<?php

namespace Drupal\su_humsci_profile\Form;

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

    $form['shortcuts']['links']['#tabledrag'] = [
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

    foreach ($this->entity->getShortcuts() as $shortcut) {
      $link_settings = $shortcut->get('link')->getValue();
      $link_options = $link_settings[0]['options'];

      $indentation = [];
      if (isset($link_options['depth']) && $link_options['depth'] > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $link_options['depth'],
        ];
      }


      $first_column = &$form['shortcuts']['links'][$shortcut->id()]['name'];
      $first_column['#prefix'] = !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '';
      $first_column['shortcut_id'] = [
        '#type' => 'hidden',
        '#value' => $shortcut->id(),
        '#attributes' => ['class' => ['shortcut-id']],
      ];
      $first_column['parent'] = [
        '#type' => 'hidden',
        '#default_value' => $link_options['parent'] ?? 0,
        '#attributes' => ['class' => ['shortcut-parent']],
      ];
      $first_column['depth'] = [
        '#type' => 'hidden',
        '#default_value' => $link_options['depth'] ?? 0,
        '#attributes' => ['class' => ['shortcut-depth']],
      ];
    }
    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    foreach ($this->entity->getShortcuts() as $shortcut) {
      $link = $shortcut->get('link')->getValue();
      $depth = $form_state->getValue([
        'shortcuts',
        'links',
        $shortcut->id(),
        'name',
        'depth',
      ]);
      $parent = $form_state->getValue([
        'shortcuts',
        'links',
        $shortcut->id(),
        'name',
        'parent',
      ]);
      $link[0]['options']['depth'] = $depth;
      $link[0]['options']['parent'] = $parent;

      dpm($link);
      $shortcut->set('link', $link);
      $shortcut->save();
    }
  }

}
