<?php

namespace Drupal\Tests\hs_blocks\Kernel\EventSubscriber;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Plugin\Context\EntityContext;
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
   * Tests the preview never hides the block, and flags it instead.
   */
  public function testPreviewIsNotHidden(): void {
    $rule = ['field' => 'title', 'operator' => 'not_empty'];
    $build = $this->buildComponent($rule, 'Hello', TRUE);

    $this->assertArrayHasKey('content', $build);
    $this->assertContains('hs-blocks-conditionally-hidden', $build['#attributes']['class']);
  }

  /**
   * Tests the bridge lowers the response to a date rule's short max-age.
   */
  public function testResponseMaxAgeTightened(): void {
    $response = new CacheableResponse();
    $response->getCacheableMetadata()->setCacheMaxAge(60);
    // Stand in for the long page max-age core sends proxies.
    $response->setMaxAge(16588800);

    $this->dispatchResponse($response);

    // s-maxage (Varnish) and max-age (browser) both expire at the flip.
    $this->assertSame(60, $response->getMaxAge());
    $this->assertSame('60', $response->headers->getCacheControlDirective('max-age'));
    $this->assertSame('60', $response->headers->getCacheControlDirective('s-maxage'));
  }

  /**
   * Tests the bridge never raises a lifetime or makes a page cacheable.
   */
  public function testResponseMaxAgeNotRaised(): void {
    // A shorter lifetime set elsewhere wins over the rule's max-age.
    $short = new CacheableResponse();
    $short->getCacheableMetadata()->setCacheMaxAge(60);
    $short->setMaxAge(30);
    $this->dispatchResponse($short);
    $this->assertSame(30, $short->getMaxAge());

    // An uncacheable response is never made cacheable.
    $uncacheable = new CacheableResponse();
    $uncacheable->getCacheableMetadata()->setCacheMaxAge(60);
    $this->dispatchResponse($uncacheable);
    $this->assertNull($uncacheable->getMaxAge());
  }

  /**
   * Tests a permanent response is left alone.
   */
  public function testResponsePermanentUntouched(): void {
    $response = new CacheableResponse();
    // No date rule in play: the metadata max-age stays permanent (-1).
    $response->setMaxAge(16588800);

    $this->dispatchResponse($response);

    $this->assertSame(16588800, $response->getMaxAge());
  }

}
