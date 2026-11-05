<?php

namespace Drupal\hs_blocks\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL, $delta = NULL, ?string $uuid = NULL, ?string $group = NULL) {
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
    ];
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Hide this block if the field'),
      '#options' => [
        'empty' => $this->t('is empty'),
        'not_empty' => $this->t('is not empty'),
        'future' => $this->t('is in the future (date fields only)'),
        'past' => $this->t('is in the past (date fields only)'),
      ],
      '#default_value' => $rule['operator'] ?? 'empty',
      '#states' => [
        'invisible' => [':input[name="field"]' => ['value' => '']],
      ],
    ];
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field = $form_state->getValue('field');
    $rule = $field ? [
      'field' => $field,
      'operator' => $form_state->getValue('operator'),
      'action' => 'hide',
    ] : NULL;

    $section = $this->sectionStorage->getSection($this->delta);
    if ($this->group) {
      $group = $section->getComponent($this->group);
      $group_config = $group->get('configuration');
      if ($rule) {
        $group_config['children'][$this->uuid]['additional']['visibility_rule'] = $rule;
      }
      else {
        unset($group_config['children'][$this->uuid]['additional']['visibility_rule']);
      }
      $group->setConfiguration($group_config);
    }
    else {
      // Stored in the component's additional values, like the attributes.
      $section->getComponent($this->uuid)->set('visibility_rule', $rule);
    }

    $this->layoutTempstore->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Gets the saved rule of the block being managed.
   *
   * @return array
   *   The rule, or an empty array.
   */
  protected function getRule(): array {
    $section = $this->sectionStorage->getSection($this->delta);
    if ($this->group) {
      $config = $section->getComponent($this->group)->get('configuration');
      $rule = $config['children'][$this->uuid]['additional']['visibility_rule'] ?? [];
    }
    else {
      $rule = $section->getComponent($this->uuid)->get('visibility_rule') ?? [];
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
    $contexts = $this->sectionStorage->getContextsDuringPreview();
    $entity = isset($contexts['layout_builder.entity']) ? $contexts['layout_builder.entity']->getContextValue() : NULL;
    if (!$entity instanceof FieldableEntityInterface) {
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
      asort($group_options);
    }
    return array_filter($options);
  }

}
