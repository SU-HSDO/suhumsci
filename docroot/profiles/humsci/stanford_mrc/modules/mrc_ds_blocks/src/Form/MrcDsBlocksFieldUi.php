<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\Component\Utility\Html;
use Drupal\mrc_ds_blocks\MrcDsBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MrcDsBlocksFieldUi.
 */
class MrcDsBlocksFieldUi implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.block'));
  }

  /**
   * MrcDsBlocksFieldUi constructor.
   *
   * @param \Drupal\Core\Block\BlockManager $block_manager
   */
  public function __construct(BlockManager $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * Alter the entity_view_display form.
   *
   * @param array $form
   *   Existing display form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   *
   * @see mrc_ds_blocks_form_entity_view_display_edit_form_alter()
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $callback_object = $form_state->getBuildInfo()['callback_object'];
    if (!$callback_object instanceof EntityDisplayFormBase) {
      throw new \InvalidArgumentException('Unkown callback object.');
    }

    $display = $callback_object->getEntity();
    $params = mrc_ds_blocks_field_ui_form_params($form, $display);

    $table = &$form['fields'];
    $blocks = $this->blockManager->getDefinitions();

    $form['#mrc_ds_blocks'] = array_keys($params->blocks);

    // Go through each block and add it to the table.
    foreach ($params->blocks as $block_id => $block) {
      // Skip if the block doesn't exist.
      if (!$this->blockManager->hasDefinition($block_id)) {
        continue;
      }

      // Block settings has changed, so update the block.
      if ($form_state->get('plugin_settings_update') == $block_id) {
        $block = $this->updateBlock($form, $form_state);
      }

      // Create the table row.
      $block_row = [
        '#attributes' => [
          'class' => ['draggable', 'tabledrag-leaf'],
          'id' => $block_id,
        ],
        '#row_type' => 'field',
        '#region_callback' => $params->region_callback,
        '#js_settings' => ['rowHandler' => 'field'],
        'human_name' => [
          '#markup' => Html::escape($blocks[$block_id]['admin_label']),
          '#prefix' => '<span class="block-label">',
          '#suffix' => '</span>',
        ],
        'weight' => [
          '#type' => 'textfield',
          '#default_value' => $block->weight,
          '#size' => 3,
          '#attributes' => ['class' => ['field-weight']],
        ],
        'parent_wrapper' => [
          'parent' => [
            '#type' => 'select',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#default_value' => $block->parent_name,
            '#attributes' => ['class' => ['field-parent']],
            '#parents' => ['fields', $block_id, 'parent'],
          ],
          'hidden_name' => [
            '#type' => 'hidden',
            '#value' => $block_id,
            '#attributes' => ['class' => ['field-name']],
          ],
        ],
        'region' => [
          '#type' => 'select',
          '#title' => $this->t('Region for @title', ['@title' => $block_id]),
          '#title_display' => 'invisible',
          '#options' => $callback_object->getRegionOptions(),
          '#default_value' => isset($block->region) ? $block->region : 'hidden',
          '#attributes' => ['class' => ['field-region']],
        ],
        'label' => [],
        'plugin' => [],
        'settings_summary' => [],
      ];

      // If the edit settings button was pushed, show the settings form.
      if ($form_state->get('plugin_settings_edit') == $block_id) {
        $block_row['settings_edit']['#cell_attributes'] = ['colspan' => 2];
        $block_row['plugin'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#array_parents' => ['fields', $block_id, 'settings_edit_form'],
          '#weight' => -5,
          // Create a settings form where hooks can pick in.
          'settings' => $this->getBlockForm($form_state, $block_id, $block->config),
          'actions' => [
            '#type' => 'actions',
            'save_settings' => $this->getButton('update', $block_id, $form_state),
            'cancel_settings' => $this->getButton('cancel', $block_id, $form_state),
          ],
        ];
        $block_row['#attributes']['class'][] = 'field-formatter-settings-editing';
      }
      else {
        // Add the edit settings button.
        $block_row['settings_edit'] = [
          '#type' => 'image_button',
          '#name' => $block_id . '_block_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => [
            'class' => ['field-plugin-settings-edit'],
            'alt' => $this->t('Edit'),
          ],
          '#op' => 'edit',
          // Do not check errors for the 'Edit' button, but make sure we get
          // the value of the 'plugin type' select.
          '#limit_validation_errors' => [['fields', $block_id, 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        ];

        $block_row['settings_edit'] += $this->getButton(NULL, $block_id, $form_state);

        // Add the delete button.
        $block->blockId = $block_id;
        $delete_route = MrcDsBlocks::getDeleteRoute($block);
        $block_row['settings_summary']['#markup'] = Link::fromTextAndUrl(t('delete'), $delete_route)
          ->toString();
      }

      // Add the block row to the table.
      $table[$block_id] = $block_row;
    }
  }

  /**
   * Get a button for the field ui form.
   *
   * @param string $type
   *   Type of button to get.
   * @param string $block_id
   *   Machine name of block.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Built button.
   */
  protected function getButton($type, $block_id, FormStateInterface $form_state) {
    // Base button that we'll add to the edit, update and save buttons.
    $object = $form_state->getBuildInfo()['callback_object'];
    $button = [
      '#field_name' => $block_id,
      '#submit' => [[$object, 'multistepSubmit']],
      '#ajax' => [
        'callback' => [$object, 'multistepAjax'],
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
    ];

    switch ($type) {
      case 'update':
        $button += [
          '#type' => 'submit',
          '#name' => $block_id . '_plugin_settings_update',
          '#value' => $this->t('Update'),
          '#op' => 'update',
        ];
        break;

      case 'cancel':
        $button += [
          '#type' => 'submit',
          '#name' => $block_id . '_plugin_settings_cancel',
          '#value' => $this->t('Cancel'),
          '#op' => 'cancel',
          // Do not check errors for the 'Cancel' button.
          '#limit_validation_errors' => [],
        ];
        break;
    }

    return $button;
  }

  /**
   * Get the configuration form for a particular block.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   * @param string $block_id
   *   Machine name of the block to load.
   * @param array $config
   *   Existing configuration of the block.
   *
   * @return array
   *   The block config form.
   */
  protected function getBlockForm(FormStateInterface $form_state, $block_id, $config = []) {
    $form = [];
    $block = $this->blockManager->createInstance($block_id, $config);
    $form = $block->buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * Update blocks when the "edit settings" dialog is closed.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   *
   * @return \stdClass
   *   The updated block.
   */
  protected function updateBlock(array $form, FormStateInterface $form_state) {

    $callback_object = $form_state->getBuildInfo()['callback_object'];
    if (!$callback_object instanceof EntityDisplayFormBase) {
      throw new \InvalidArgumentException('Unkown callback object.');
    }

    $display = $callback_object->getEntity();

    $parameters = mrc_ds_blocks_field_ui_form_params($form, $display);
    $block_id = $form_state->get('plugin_settings_update');
    $blocks = $parameters->blocks;
    $config = &$blocks[$block_id]->config;

    $form_values = $form_state->getValue([
      'fields',
      $block_id,
      'plugin',
      'settings',
    ]);

    // Set the block configurations and save the block to the display.
    if (!empty($form_values)) {
      $this->matchConfig($config, $form_values);
      return mrc_ds_blocks_save($blocks[$block_id]);
    }
  }

  /**
   * Match original configurations with the new values.
   *
   * The add block form and edit settings form has different structures mainly
   * because while in the edit dialog, the form has '#tree' => TRUE, so the
   * submitted value structure doesn't match the original configurations. So we
   * navigate through the original configuration and the submitted values to
   * retain the original configuration structure with new values.
   *
   * @param mixed $config
   *   Original configuration.
   * @param mixed $form_values
   *   Newly submitted values.
   */
  protected function matchConfig(&$config, $form_values) {
    if (!is_array($config)) {
      return;
    }

    foreach ($config as $key => &$value) {
      if (is_array($value)) {
        $this->matchConfig($value, $form_values);
        continue;
      }
      $value = $this->findValue($key, $form_values);
    }
  }

  /**
   * Find a nested value within an array.
   *
   * @param string $key
   *   Key of the desired value.
   * @param array $values
   *   Array ideally with the key and value nested somewhere.
   *
   * @return mixed|null
   */
  protected function findValue($key, array $values) {
    if (isset($values[$key])) {
      // Found the key, return its value.
      return $values[$key];
    }

    // The key didn't exist, so continue the depth of the array.
    foreach ($values as $value) {
      if (is_array($value)) {
        // Recursively dive deeper into the values array.
        return $this->findValue($key, $value);
      }
    }
    // No results found.
    return NULL;
  }

  /**
   * Get the display region for the row.
   *
   * @param array $row
   *   Table row.
   *
   * @return string
   *   Display region.
   */
  public static function getRowRegion(&$row) {
    return $row['region']['#value'];
  }

}
