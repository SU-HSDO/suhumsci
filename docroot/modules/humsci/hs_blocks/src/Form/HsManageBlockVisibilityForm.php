<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\hs_blocks\VisibilityRuleEvaluator;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage the visibility rule of a block.
 *
 * Used for blocks placed directly in a section and for blocks nested in a
 * group block, which store their component data in different places.
 *
 * @package Drupal\hs_blocks\Form
 */
class HsManageBlockVisibilityForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The component uuid.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The parent group block uuid, if the block is nested in a group.
   *
   * @var string|null
   */
  protected $group;

  /**
   * Constructs a new HsManageBlockVisibilityForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layoutTempstore
   *   The layout tempstore.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(protected LayoutTempstoreRepositoryInterface $layoutTempstore, protected EntityFieldManagerInterface $entityFieldManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'hs_blocks_manage_block_visibility';
  }

  /**
   * Builds the visibility form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface|null $section_storage
   *   The section storage being configured.
   * @param int|string|null $delta
   *   The delta of the section.
   * @param string|null $uuid
   *   The UUID of the block being updated.
   * @param string|null $group
   *   The UUID of the group block containing the block, if any.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL, $delta = NULL, ?string $uuid = NULL, ?string $group = NULL): array {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;
    $this->group = $group;

    $rule = $this->getRule();

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($uuid);
    $form['#tree'] = TRUE;

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Hide this block based on a field of the content being displayed.') . '</p>',
    ];
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $this->getFieldOptions(),
      '#empty_option' => $this->t('- Always show this block -'),
      '#default_value' => $rule['field'] ?? '',
      '#ajax' => [
        'callback' => '::updateVisibilitySettings',
        'wrapper' => 'hs-blocks-visibility-settings',
        'event' => 'change',
      ],
    ];

    // Offer only the operators that fit the chosen field: every field can be
    // empty or not, date fields add the two date operators, and only a field
    // with a separate start and end adds the start/end choice. The field select
    // rebuilds this section over Ajax, so it always matches the current field.
    $selected_field = (string) ($form_state->getValue('field') ?? $rule['field'] ?? '');
    $definitions = $this->getFieldDefinitions();
    $definition = ($selected_field && isset($definitions[$selected_field])) ? $definitions[$selected_field] : NULL;
    $is_date_field = $definition && VisibilityRuleEvaluator::isDateField($definition);
    $has_date_range = $definition && VisibilityRuleEvaluator::hasDateRange($definition);

    $form['settings'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'hs-blocks-visibility-settings'],
    ];

    $operator_options = [
      'empty' => $this->t('is empty'),
      'not_empty' => $this->t('is not empty'),
    ];
    if ($is_date_field) {
      $operator_options['future'] = $this->t('is in the future');
      $operator_options['past'] = $this->t('is in the past');
    }
    $form['settings']['operator'] = [
      '#type' => 'select',
      // Keep the flat name so the stored rule and the #states below are
      // unaffected by moving the element into the Ajax wrapper.
      '#parents' => ['operator'],
      '#title' => $this->t('Hide this block if the field'),
      '#options' => $operator_options,
      '#default_value' => $rule['operator'] ?? 'empty',
      '#states' => [
        'invisible' => [':input[name="field"]' => ['value' => '']],
      ],
    ];
    if ($is_date_field) {
      $form['settings']['operator']['#description'] = $this->t('Compared to the current date and time, to the minute. "Is in the future" hides the block until the date arrives; "is in the past" hides it from that moment on. A time equal to now counts as past.');
    }

    // Date range and event (smart date) fields have a start and an end, so let
    // the editor choose which one the date operators compare. For example, an
    // RSVP block hidden once an event begins compares the start, not the end.
    if ($has_date_range) {
      $form['settings']['boundary'] = [
        '#type' => 'select',
        '#parents' => ['boundary'],
        '#title' => $this->t('Compare using'),
        '#options' => [
          'end' => $this->t('End date'),
          'start' => $this->t('Start date'),
        ],
        '#default_value' => $rule['boundary'] ?? 'end',
        '#description' => $this->t('Only date range and event (smart date) fields have both. Example: to hide an RSVP block the moment an event starts, compare the <strong>Start date</strong> and choose "is in the past".'),
        '#states' => [
          'visible' => [
            ':input[name="operator"]' => [
              ['value' => 'future'],
              'or',
              ['value' => 'past'],
            ],
          ],
        ],
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }

    // Workaround for core bug:
    // https://www.drupal.org/project/drupal/issues/2897377.
    $form['#id'] = Html::cleanCssIdentifier($this->getFormId());
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $field = $form_state->getValue('field');
    $operator = $form_state->getValue('operator');
    if (!$field || !in_array($operator, VisibilityRuleEvaluator::DATE_OPERATORS, TRUE)) {
      return;
    }

    $definitions = $this->getFieldDefinitions();
    if (!isset($definitions[$field]) || !VisibilityRuleEvaluator::isDateField($definitions[$field])) {
      $form_state->setErrorByName('operator', $this->t('"Is in the future" and "is in the past" can only be used with date fields.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $field = $form_state->getValue('field');
    $rule = $field ? [
      'field' => $field,
      'operator' => $form_state->getValue('operator'),
    ] : NULL;

    // Only a date rule on a field that has a start and an end needs to record
    // which one it compares; everything else uses the single value.
    if ($rule && in_array($rule['operator'], VisibilityRuleEvaluator::DATE_OPERATORS, TRUE)) {
      $definitions = $this->getFieldDefinitions();
      if (isset($definitions[$field]) && VisibilityRuleEvaluator::hasDateRange($definitions[$field])) {
        $rule['boundary'] = $form_state->getValue('boundary') === 'start' ? 'start' : 'end';
      }
    }

    $section = $this->sectionStorage->getSection($this->delta);
    // The block or its group may have been removed since the form was opened,
    // for example from a stale off-canvas link, so only write to a component
    // that still exists; otherwise a child with no block plugin would be saved
    // into the layout.
    $components = $section->getComponents();
    if ($this->group) {
      if (isset($components[$this->group])) {
        $group = $components[$this->group];
        $group_config = $group->get('configuration');
        if (isset($group_config['children'][$this->uuid])) {
          $group_config['children'][$this->uuid]['additional']['visibility_rule'] = $rule;
          $group->setConfiguration($group_config);
        }
      }
    }
    elseif (isset($components[$this->uuid])) {
      // Stored in the component's additional values, like the attributes. A
      // removed rule is stored as NULL in both places; SectionComponent has no
      // method to unset an additional property.
      $components[$this->uuid]->set('visibility_rule', $rule);
    }

    $this->layoutTempstore->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Ajax callback: rebuilds the operators to match the selected field.
   *
   * @param array $form
   *   The rebuilt form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the form to replace.
   */
  public function updateVisibilitySettings(array $form, FormStateInterface $form_state): array {
    return $form['settings'];
  }

  /**
   * Gets the saved rule of the block being managed.
   *
   * @return array
   *   The rule, or an empty array.
   */
  protected function getRule(): array {
    $section = $this->sectionStorage->getSection($this->delta);
    // The component may be gone if the form was opened from a stale link.
    $components = $section->getComponents();
    if ($this->group) {
      if (!isset($components[$this->group])) {
        return [];
      }
      $config = $components[$this->group]->get('configuration');
      $rule = $config['children'][$this->uuid]['additional']['visibility_rule'] ?? [];
    }
    else {
      if (!isset($components[$this->uuid])) {
        return [];
      }
      $rule = $components[$this->uuid]->get('visibility_rule') ?? [];
    }
    return is_array($rule) ? $rule : [];
  }

  /**
   * Gets the definitions of the fields a rule can be based on.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Field definitions keyed by field name.
   */
  protected function getFieldDefinitions(): array {
    // Default layouts have no real entity, so use the preview sample entity.
    $entity = VisibilityRuleEvaluator::getContextEntity($this->sectionStorage->getContextsDuringPreview());
    if (!$entity) {
      return [];
    }

    // Base fields such as the ID, UUID and revision data are not something an
    // editor can reasonably base a rule on, so only the fields added to the
    // bundle and the label (title) are offered.
    $label_key = $entity->getEntityType()->getKey('label');
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    return array_filter(
      $definitions,
      fn (FieldDefinitionInterface $definition) => $definition instanceof FieldConfigInterface || $definition->getName() === $label_key
    );
  }

  /**
   * Lists the fields of the entity the layout is displaying.
   *
   * @return array
   *   Field labels keyed by field name, grouped so editors can find the date
   *   fields the date operators need.
   */
  protected function getFieldOptions(): array {
    $date_group = (string) $this->t('Date fields');
    $other_group = (string) $this->t('Other fields');

    $options = [$date_group => [], $other_group => []];
    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      $group = VisibilityRuleEvaluator::isDateField($definition) ? $date_group : $other_group;
      $options[$group][$field_name] = $this->t('@label (@name)', [
        '@label' => $definition->getLabel(),
        '@name' => $field_name,
      ]);
    }
    foreach ($options as &$group_options) {
      // The labels are TranslatableMarkup objects, so sort by their rendered
      // string to get the alphabetical order editors expect.
      uasort($group_options, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
    }
    return array_filter($options);
  }

}
