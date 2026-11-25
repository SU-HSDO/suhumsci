<?php

namespace Drupal\Tests\hs_blocks\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Url;
use Drupal\hs_blocks\Form\HsManageBlockVisibilityForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the manage visibility form stores the rule on the right component.
 */
#[CoversClass(HsManageBlockVisibilityForm::class)]
#[Group('hs_blocks')]
#[RunTestsInSeparateProcesses]
class HsManageBlockVisibilityFormTest extends KernelTestBase {

  const BLOCK_UUID = '11111111-1111-4111-8111-111111111111';

  const GROUP_UUID = '22222222-2222-4222-8222-222222222222';

  const CHILD_UUID = '33333333-3333-4333-8333-333333333333';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'datetime',
    'datetime_range',
    'filter',
    'block',
    'layout_builder',
    'layout_discovery',
    'hs_blocks',
  ];

  /**
   * The section holding the components under test.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $section;

  /**
   * The form under test.
   *
   * @var \Drupal\hs_blocks\Form\HsManageBlockVisibilityForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'node', 'filter']);
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'date'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Date',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_range',
      'entity_type' => 'node',
      'type' => 'daterange',
      'settings' => ['datetime_type' => 'datetime'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_range',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Range',
    ])->save();

    $this->section = new Section('layout_onecol');
    $this->section->appendComponent(new SectionComponent(self::BLOCK_UUID, 'content', ['id' => 'system_powered_by_block']));
    $this->section->appendComponent(new SectionComponent(self::GROUP_UUID, 'content', [
      'id' => 'group_block:test',
      'children' => [self::CHILD_UUID => ['id' => 'system_powered_by_block']],
    ]));

    $this->container->set('layout_builder.tempstore_repository', $this->createMock(LayoutTempstoreRepositoryInterface::class));
    $this->form = HsManageBlockVisibilityForm::create($this->container);
  }

  /**
   * Builds the form for a block, then submits it with the given values.
   *
   * @param string $uuid
   *   The block being managed.
   * @param string|null $group
   *   The group containing the block.
   * @param array $values
   *   The submitted values.
   *
   * @return array
   *   The built form.
   */
  protected function submit(string $uuid, ?string $group, array $values): array {
    $node = Node::create(['type' => 'page', 'title' => 'Hello']);

    $storage = $this->createMock(SectionStorageInterface::class);
    $storage->method('getSection')->willReturn($this->section);
    $storage->method('getContextsDuringPreview')->willReturn(['layout_builder.entity' => EntityContext::fromEntity($node)]);
    $storage->method('getLayoutBuilderUrl')->willReturn(Url::fromRoute('<front>'));

    $form_state = new FormState();
    $form = $this->form->buildForm([], $form_state, $storage, 0, $uuid, $group);
    $form_state->setValues($values);
    $this->form->submitForm($form, $form_state);
    return $form;
  }

  /**
   * Builds the form for the top-level block with the given submitted values.
   *
   * @param array $values
   *   The values already in the form state, as the Ajax field select leaves
   *   them when the editor picks a field.
   *
   * @return array
   *   The built form.
   */
  protected function build(array $values): array {
    $node = Node::create(['type' => 'page', 'title' => 'Hello']);
    $storage = $this->createMock(SectionStorageInterface::class);
    $storage->method('getSection')->willReturn($this->section);
    $storage->method('getContextsDuringPreview')->willReturn(['layout_builder.entity' => EntityContext::fromEntity($node)]);

    $form_state = new FormState();
    $form_state->setValues($values);
    return $this->form->buildForm([], $form_state, $storage, 0, self::BLOCK_UUID, NULL);
  }

  /**
   * Gets the rule stored on the top-level block.
   *
   * @return mixed
   *   The rule.
   */
  protected function getBlockRule(): mixed {
    return $this->section->getComponent(self::BLOCK_UUID)->get('visibility_rule');
  }

  /**
   * Tests the operator and boundary options follow the selected field type.
   */
  public function testOperatorOptionsDependOnField(): void {
    // With no field chosen, only the value operators are offered.
    $form = $this->build([]);
    $this->assertSame(['empty', 'not_empty'], array_keys($form['settings']['operator']['#options']));
    $this->assertArrayNotHasKey('boundary', $form['settings']);

    // A non-date field keeps just the value operators.
    $form = $this->build(['field' => 'title']);
    $this->assertSame(['empty', 'not_empty'], array_keys($form['settings']['operator']['#options']));
    $this->assertArrayNotHasKey('boundary', $form['settings']);

    // A single-value date field adds the date operators, but has no start/end.
    $form = $this->build(['field' => 'field_date']);
    $this->assertSame(['empty', 'not_empty', 'future', 'past'], array_keys($form['settings']['operator']['#options']));
    $this->assertArrayNotHasKey('boundary', $form['settings']);

    // A date range field adds both the date operators and the start/end choice.
    $form = $this->build(['field' => 'field_range']);
    $this->assertSame(['empty', 'not_empty', 'future', 'past'], array_keys($form['settings']['operator']['#options']));
    $this->assertArrayHasKey('boundary', $form['settings']);
  }

  /**
   * Tests the field list and a rule saved on a top-level block.
   */
  public function testTopLevelBlock(): void {
    $form = $this->submit(self::BLOCK_UUID, NULL, ['field' => 'title', 'operator' => 'empty']);

    $this->assertArrayHasKey('title', $form['field']['#options']['Other fields']);
    $this->assertArrayHasKey('field_date', $form['field']['#options']['Date fields']);
    // Base fields other than the title are not offered.
    $this->assertSame(['title'], array_keys($form['field']['#options']['Other fields']));
    $rule = $this->getBlockRule();
    $this->assertSame('title', $rule['field']);
    $this->assertSame('empty', $rule['operator']);

    // Choosing no field clears the rule.
    $this->submit(self::BLOCK_UUID, NULL, ['field' => '', 'operator' => 'empty']);
    $this->assertEmpty($this->getBlockRule());
  }

  /**
   * Tests the date operators are only accepted for date fields.
   */
  public function testDateOperatorValidation(): void {
    $this->assertSame([], $this->validate('field_date', 'future'));
    $this->assertSame([], $this->validate('title', 'empty'));
    $this->assertCount(1, $this->validate('title', 'past'));
  }

  /**
   * Validates the form with the given values.
   *
   * @param string $field
   *   The selected field.
   * @param string $operator
   *   The selected operator.
   *
   * @return array
   *   The form errors.
   */
  protected function validate(string $field, string $operator): array {
    $node = Node::create(['type' => 'page', 'title' => 'Hello']);
    $storage = $this->createMock(SectionStorageInterface::class);
    $storage->method('getSection')->willReturn($this->section);
    $storage->method('getContextsDuringPreview')->willReturn(['layout_builder.entity' => EntityContext::fromEntity($node)]);

    $form_state = new FormState();
    $form = $this->form->buildForm([], $form_state, $storage, 0, self::BLOCK_UUID, NULL);
    $form_state->setValues(['field' => $field, 'operator' => $operator]);
    $this->form->validateForm($form, $form_state);
    return $form_state->getErrors();
  }

  /**
   * Tests a rule saved on a block nested in a group block.
   */
  public function testGroupChild(): void {
    $this->submit(self::CHILD_UUID, self::GROUP_UUID, ['field' => 'title', 'operator' => 'not_empty']);

    $config = $this->section->getComponent(self::GROUP_UUID)->get('configuration');
    $this->assertSame(
      ['field' => 'title', 'operator' => 'not_empty'],
      $config['children'][self::CHILD_UUID]['additional']['visibility_rule']
    );

    // Clearing the rule stores NULL, the same as a top-level block.
    $this->submit(self::CHILD_UUID, self::GROUP_UUID, ['field' => '', 'operator' => 'empty']);
    $config = $this->section->getComponent(self::GROUP_UUID)->get('configuration');
    $this->assertNull($config['children'][self::CHILD_UUID]['additional']['visibility_rule']);
  }

  /**
   * Tests the start/end boundary is stored only for range-field date rules.
   */
  public function testDateBoundaryStored(): void {
    // A range field offers the boundary option; the chosen value is stored.
    $this->submit(self::BLOCK_UUID, NULL, ['field' => 'field_range', 'operator' => 'past', 'boundary' => 'start']);
    $rule = $this->getBlockRule();
    $this->assertSame('field_range', $rule['field']);
    $this->assertSame('start', $rule['boundary']);

    // A single-value date field has no start/end, so no boundary is stored.
    $this->submit(self::BLOCK_UUID, NULL, ['field' => 'field_date', 'operator' => 'future']);
    $this->assertArrayNotHasKey('boundary', $this->getBlockRule());

    // A non-date rule never stores a boundary.
    $this->submit(self::BLOCK_UUID, NULL, ['field' => 'field_range', 'operator' => 'empty']);
    $this->assertArrayNotHasKey('boundary', $this->getBlockRule());
  }

  /**
   * Tests a stale link to a removed block neither fatals nor saves a bad child.
   */
  public function testMissingComponent(): void {
    $missing = '44444444-4444-4444-8444-444444444444';

    // A top-level block that no longer exists is handled without a fatal.
    $form = $this->submit($missing, NULL, ['field' => 'title', 'operator' => 'empty']);
    $this->assertArrayHasKey('field', $form);

    // A group child that no longer exists is not written into the group as a
    // child with no block plugin.
    $this->submit($missing, self::GROUP_UUID, ['field' => 'title', 'operator' => 'empty']);
    $config = $this->section->getComponent(self::GROUP_UUID)->get('configuration');
    $this->assertArrayNotHasKey($missing, $config['children']);

    // A missing group does not fatal either.
    $form = $this->submit($missing, $missing, ['field' => 'title', 'operator' => 'empty']);
    $this->assertArrayHasKey('field', $form);
  }

}
