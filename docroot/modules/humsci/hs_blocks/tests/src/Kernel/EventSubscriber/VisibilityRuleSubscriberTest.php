<?php

namespace Drupal\Tests\hs_blocks\Kernel\EventSubscriber;

use Drupal\Core\Plugin\Context\EntityContext;
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
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
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
    $rule = ['field' => 'title', 'operator' => 'not_empty', 'action' => 'hide'];
    $build = $this->buildComponent($rule, 'Hello');

    $this->assertArrayNotHasKey('content', $build);
    $this->assertContains('node:1', $build['#cache']['tags']);
  }

  /**
   * Tests a rule that does not match leaves the block rendered.
   */
  public function testVisibleComponent(): void {
    $rule = ['field' => 'title', 'operator' => 'empty', 'action' => 'hide'];
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
    $rule = ['field' => 'title', 'operator' => 'not_empty', 'action' => 'hide'];
    $build = $this->buildComponent($rule, 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('hs-blocks-conditionally-hidden', $build['#attributes']['class']);
  }

}
