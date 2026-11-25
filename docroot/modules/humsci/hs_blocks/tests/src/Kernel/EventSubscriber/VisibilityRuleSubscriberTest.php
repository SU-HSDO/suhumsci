<?php

namespace Drupal\Tests\hs_blocks\Kernel\EventSubscriber;

use Drupal\Core\Cache\CacheableResponse;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    // A timestamp field lets a date rule set a known flip time in the build.
    FieldStorageConfig::create([
      'field_name' => 'field_stamp',
      'entity_type' => 'node',
      'type' => 'timestamp',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_stamp',
      'entity_type' => 'node',
      'bundle' => 'page',
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
   * @param int|null $stamp
   *   A value for the node's timestamp field, for a date rule to compare.
   *
   * @return array
   *   The component render array.
   */
  protected function buildComponent(array $rule, string $title, bool $in_preview = FALSE, ?int $stamp = NULL): array {
    $node = Node::create(['type' => 'page', 'title' => $title]);
    if ($stamp !== NULL) {
      $node->set('field_stamp', $stamp);
    }
    $node->save();

    $component = new SectionComponent($this->container->get('uuid')->generate(), 'content', [
      'id' => 'system_powered_by_block',
    ], ['visibility_rule' => $rule]);

    $contexts = ['layout_builder.entity' => EntityContext::fromEntity($node)];
    return $component->toRenderArray($contexts, $in_preview);
  }

  /**
   * Runs the response bridge over a response as the main request.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to pass through the subscriber.
   */
  protected function dispatchResponse(Response $response): void {
    $event = new ResponseEvent(
      $this->container->get('http_kernel'),
      Request::create('/'),
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );
    $this->container->get('hs_blocks.visibility_rule_subscriber')->onResponse($event);
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
   * Tests the preview badges a block whose rule currently hides it.
   */
  public function testPreviewBadgesRuleThatHides(): void {
    // "not_empty" on a filled title would hide the block on the live site.
    $build = $this->buildComponent(['field' => 'title', 'operator' => 'not_empty'], 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('hs-blocks-has-visibility-rule', $build['#attributes']['class']);
  }

  /**
   * Tests the badge shows even when the rule does not currently hide the block.
   */
  public function testPreviewBadgesRuleThatDoesNotHide(): void {
    // "empty" on a filled title does not hide the block, but still has a rule.
    $build = $this->buildComponent(['field' => 'title', 'operator' => 'empty'], 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('hs-blocks-has-visibility-rule', $build['#attributes']['class']);
  }

  /**
   * Tests a block without a rule gets no badge in the preview.
   */
  public function testPreviewNoRuleNoBadge(): void {
    $build = $this->buildComponent([], 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertNotContains('hs-blocks-has-visibility-rule', $build['#attributes']['class'] ?? []);
  }

  /**
   * Tests the bridge lowers the response to a date rule's next flip.
   */
  public function testResponseMaxAgeTightened(): void {
    // A future date rule shortens the block's life to the seconds until it
    // flips; onBuildLive collects that as the component builds.
    $now = \Drupal::time()->getRequestTime();
    $this->buildComponent(['field' => 'field_stamp', 'operator' => 'future'], 'Soon', FALSE, $now + 60);

    $response = new CacheableResponse();
    // Stand in for the long page max-age core sends proxies.
    $response->setMaxAge(16588800);
    $this->dispatchResponse($response);

    // s-maxage (Varnish) and max-age (browser) both expire at the flip.
    $this->assertSame(60, $response->getMaxAge());
    $this->assertSame('60', $response->headers->getCacheControlDirective('max-age'));
    $this->assertSame('60', $response->headers->getCacheControlDirective('s-maxage'));
  }

  /**
   * Tests a page with no date rule keeps its full cache lifetime.
   *
   * The bridge forwards only a date rule's own flip time, never the page's
   * whole bubbled max-age, so an unrelated short-lived element on the page
   * never shortens how long Varnish caches it.
   */
  public function testResponseUntouchedWithoutDateRule(): void {
    // A value rule sets no finite max-age, so nothing is collected to forward.
    $this->buildComponent(['field' => 'title', 'operator' => 'not_empty'], 'Hello');

    $response = new CacheableResponse();
    // Some unrelated element bubbled a short max-age onto the page.
    $response->getCacheableMetadata()->setCacheMaxAge(60);
    $response->setMaxAge(16588800);
    $this->dispatchResponse($response);

    // The long page lifetime is left untouched.
    $this->assertSame(16588800, $response->getMaxAge());
  }

  /**
   * Tests the bridge never raises a lifetime or makes a page cacheable.
   */
  public function testResponseMaxAgeNotRaised(): void {
    $now = \Drupal::time()->getRequestTime();

    // A shorter lifetime set elsewhere wins over the rule's flip time.
    $this->buildComponent(['field' => 'field_stamp', 'operator' => 'future'], 'Soon', FALSE, $now + 3600);
    $short = new CacheableResponse();
    $short->setMaxAge(30);
    $this->dispatchResponse($short);
    $this->assertSame(30, $short->getMaxAge());

    // An uncacheable response is never made cacheable.
    $this->buildComponent(['field' => 'field_stamp', 'operator' => 'future'], 'Soon', FALSE, $now + 3600);
    $uncacheable = new CacheableResponse();
    $this->dispatchResponse($uncacheable);
    $this->assertNull($uncacheable->getMaxAge());
  }

  /**
   * Tests a response with no date rule in play is left alone.
   */
  public function testResponsePermanentUntouched(): void {
    $response = new CacheableResponse();
    $response->setMaxAge(16588800);

    $this->dispatchResponse($response);

    $this->assertSame(16588800, $response->getMaxAge());
  }

}
