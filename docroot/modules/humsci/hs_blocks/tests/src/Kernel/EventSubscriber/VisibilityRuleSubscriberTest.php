<?php

namespace Drupal\Tests\hs_blocks\Kernel\EventSubscriber;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_blocks\EventSubscriber\VisibilityRuleSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests components are hidden by their visibility rule.
 */
#[CoversClass(VisibilityRuleSubscriber::class)]
#[Group('hs_blocks')]
#[RunTestsInSeparateProcesses]
class VisibilityRuleSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'datetime',
    'block',
    'layout_builder',
    'layout_discovery',
    'hs_blocks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'node', 'filter']);
    $this->installSchema('hs_blocks', ['hs_blocks_visibility_flip']);
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'datetime'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Date',
    ])->save();
  }

  /**
   * Builds the render array of a component for a node.
   *
   * @param array $rule
   *   The visibility rule.
   * @param string $title
   *   The node title.
   * @param bool $in_preview
   *   Whether the component is built in the Layout Builder UI.
   *
   * @return array
   *   The component render array.
   */
  protected function buildComponent(array $rule, string $title, bool $in_preview = FALSE): array {
    $node = Node::create(['type' => 'page', 'title' => $title]);
    $node->save();

    $component = new SectionComponent($this->container->get('uuid')->generate(), 'content', [
      'id' => 'system_powered_by_block',
    ], ['visibility_rule' => $rule]);

    $contexts = ['layout_builder.entity' => EntityContext::fromEntity($node)];
    return $component->toRenderArray($contexts, $in_preview);
  }

  /**
   * Tests a matching rule empties the build but keeps cache metadata.
   */
  public function testHiddenComponent(): void {
    $rule = ['field' => 'title', 'operator' => 'not_empty'];
    $build = $this->buildComponent($rule, 'Hello');

    $this->assertArrayNotHasKey('content', $build);
    $this->assertContains('node:1', $build['#cache']['tags']);
  }

  /**
   * Tests a rule that does not match leaves the block rendered.
   */
  public function testVisibleComponent(): void {
    $rule = ['field' => 'title', 'operator' => 'empty'];
    $build = $this->buildComponent($rule, 'Hello');

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('node:1', $build['#cache']['tags']);
  }

  /**
   * Tests a component without a rule is untouched.
   */
  public function testNoRule(): void {
    $this->assertArrayHasKey('content', $this->buildComponent([], 'Hello'));
  }

  /**
   * Tests the preview never hides the block, and flags it instead.
   */
  public function testPreviewIsNotHidden(): void {
    $rule = ['field' => 'title', 'operator' => 'not_empty'];
    $build = $this->buildComponent($rule, 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('hs-blocks-conditionally-hidden', $build['#attributes']['class']);
  }

  /**
   * Tests a future date rule records a flip for the entity on the live page.
   */
  public function testDateRuleRecordsFlip(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Hello',
      'field_date' => '2999-01-01T00:00:00',
    ]);
    $node->save();

    $component = new SectionComponent($this->container->get('uuid')->generate(), 'content', [
      'id' => 'system_powered_by_block',
    ], ['visibility_rule' => ['field' => 'field_date', 'operator' => 'future']]);
    $component->toRenderArray(['layout_builder.entity' => EntityContext::fromEntity($node)], FALSE);
    // The subscriber flushes on kernel terminate, which kernel tests do not
    // dispatch, so flush here to assert what the terminate handler would write.
    $this->container->get('hs_blocks.visibility_flip_tracker')->flush();

    $flip = $this->container->get('database')
      ->select('hs_blocks_visibility_flip', 'f')
      ->fields('f', ['flip_time'])
      ->condition('cache_tag', 'node:' . $node->id())
      ->execute()
      ->fetchField();
    $this->assertNotFalse($flip);
    $this->assertGreaterThan($this->container->get('datetime.time')->getRequestTime(), (int) $flip);
  }

  /**
   * Tests the preview does not record a flip.
   */
  public function testPreviewDoesNotRecordFlip(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Hello',
      'field_date' => '2999-01-01T00:00:00',
    ]);
    $node->save();

    $component = new SectionComponent($this->container->get('uuid')->generate(), 'content', [
      'id' => 'system_powered_by_block',
    ], ['visibility_rule' => ['field' => 'field_date', 'operator' => 'future']]);
    $component->toRenderArray(['layout_builder.entity' => EntityContext::fromEntity($node)], TRUE);
    // Even after a flush, a preview build records nothing.
    $this->container->get('hs_blocks.visibility_flip_tracker')->flush();

    $count = $this->container->get('database')
      ->select('hs_blocks_visibility_flip', 'f')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame('0', $count);
  }

}
