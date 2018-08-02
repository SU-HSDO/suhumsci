<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactory;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\mrc_ds_blocks\MrcDsBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a form for adding a block to a bundle.
 */
class MrcDsBlocksAddForm extends FormBase {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the block.
   *
   * @var string
   */
  protected $context = 'view';

  /**
   * The mode for the display.
   *
   * @var string
   */
  protected $mode;

  /**
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * @var \Drupal\Core\Plugin\PluginFormFactory
   */
  protected $pluginFormFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('plugin_form.factory')
    );
  }

  public function __construct(BlockManager $block_manager, LazyContextRepository $context_repository, CurrentRouteMatch $route_match, PluginFormFactory $plugin_form) {
    $this->blockManager = $block_manager;
    $this->contextRepository = $context_repository;
    $this->routeMatch = $route_match;
    $this->pluginFormFactory = $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mrc_ds_blocks_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL, $block_id = NULL) {

    $this->mode = \Drupal::request()->get('view_mode_name');

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('context')) {
      $form_state->set('context', $context);
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('step')) {
      $form_state->set('step', 'selection');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');
    $form['#attached']['library'][] = 'block/drupal.block.admin';

    if ($this->blockManager->hasDefinition($block_id)) {
      $this->buildConfigurationForm($form, $form_state, $block_id);
      return $form;
    }

    $this->buildSelectionForm($form, $form_state);
    return $form;
  }

  private function buildSelectionForm(array &$form, FormStateInterface $form_state) {
    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    // Order by category, and then by admin label.
    $definitions = $this->blockManager->getSortedDefinitions($definitions);

    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $form['blocks'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['block-add-table']],
    ];

    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];

      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['admin_label'],
        ],
      ];

      $row['category']['data'] = [
        '#markup' => $plugin_definition['category'],
      ];

      $route = $this->routeMatch->getRouteName() . '.block';
      $route = str_replace('.block.block', '.block', $route);
      $parameters = ['block_id' => $plugin_id] + $this->getParameters();
      $add_url = Url::fromRoute($route, $parameters);

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'add' => [
            'title' => $this->t('Place block'),
            'url' => $add_url,
          ],
        ],
      ];

      $rows[] = $row;
    }

    $headers = [
      ['data' => $this->t('Block')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Operations')],
    ];

    $form['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];
  }

  /**
   * Process the current parameters into an array.
   *
   * @return array
   */
  private function getParameters() {
    $raw_parameters = $this->routeMatch->getRawParameters();
    $parameters = $this->routeMatch->getParameters();

    $return_params = [];
    foreach ($parameters as $key => $value) {
      if ($raw_value = $raw_parameters->get($key)) {
        $return_params[$key] = $raw_value;
        continue;
      }

      $return_params[$key] = $parameters->get($key);
    }

    return $return_params;
  }

  /**
   * Build the formatter configuration form.
   */
  private function buildConfigurationForm(array &$form, FormStateInterface $form_state, $block_id) {

    $form['block_id'] = [
      '#type' => 'hidden',
      '#value' => $block_id,
    ];

    $form['block_config'] = [];

    $block = $this->blockManager->createInstance($block_id);

    $subform_state = SubformState::createForSubform($form['block_config'], $form, $form_state);
    $form['block_config'] = $this->getPluginForm($block)
      ->buildConfigurationForm($form['block_config'], $subform_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Block'),
      '#button_type' => 'primary',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_id = $form_state->getValue('block_id');

    $sub_form_state = SubformState::createForSubform($form['block_config'], $form, $form_state);
    // Call the plugin submit handler.
    $block = $this->blockManager->createInstance($block_id);
    $this->getPluginForm($block)
      ->submitConfigurationForm($form, $sub_form_state);

    $form_state->cleanValues();

    $new_block = (object) [
      'blockId' => $block_id,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'mode' => $this->mode,
      'context' => $this->context,
      'config' => [],
      'parent_name' => '',
      'weight' => 20,
    ];

    foreach ($form_state->getValues() as $key => $value) {
      $new_block->config[$key] = $value;
    }
    unset($new_block->config['block_id']);
    mrc_ds_blocks_save($new_block);
    drupal_set_message(t('New block %label successfully added.', ['%label' => $block_id]));
    $form_state->setRedirectUrl(MrcDsBlocks::getFieldUiRoute($new_block));
  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the block.
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'configure');
    }
    return $block;
  }

}
