<?php

namespace Drupal\Tests\hs_blocks\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\hs_blocks\VisibilityFlipTracker;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the date-based visibility flip tracker.
 */
#[CoversClass(VisibilityFlipTracker::class)]
#[Group('hs_blocks')]
#[RunTestsInSeparateProcesses]
class VisibilityFlipTrackerTest extends KernelTestBase {

  /**
   * A fixed current time the flips are scheduled relative to.
   */
  const NOW = 1000000;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'hs_blocks'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('hs_blocks', ['hs_blocks_visibility_flip']);
  }

  /**
   * Builds a tracker with a fixed current time and the given invalidator.
   *
   * @param int $now
   *   The request time the tracker should see.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The invalidator to use.
   *
   * @return \Drupal\hs_blocks\VisibilityFlipTracker
   *   The tracker.
   */
  protected function buildTracker(int $now, CacheTagsInvalidatorInterface $invalidator): VisibilityFlipTracker {
    $time = $this->createStub(TimeInterface::class);
    $time->method('getRequestTime')->willReturn($now);
    return new VisibilityFlipTracker($this->container->get('database'), $invalidator, $time);
  }

  /**
   * The absolute flip time a relative offset from the fixed current time gives.
   *
   * @param int $offset
   *   Seconds after the fixed current time.
   *
   * @return int
   *   The absolute timestamp.
   */
  protected function flipAt(int $offset): int {
    return self::NOW + $offset;
  }

  /**
   * Reads the pending flips as a cache_tag => flip_time map.
   *
   * @return array<string, string>
   *   The stored rows.
   */
  protected function storedFlips(): array {
    return $this->container->get('database')
      ->select('hs_blocks_visibility_flip', 'f')
      ->fields('f', ['cache_tag', 'flip_time'])
      ->execute()
      ->fetchAllKeyed();
  }

  /**
   * Tests a non-positive max-age never schedules a flip.
   */
  public function testNothingRecordedWithoutUpcomingFlip(): void {
    $tracker = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $tracker->record(['node:1'], -1);
    $tracker->record(['node:1'], 0);
    $this->assertEmpty($this->storedFlips());
  }

  /**
   * Tests a recorded flip stores the absolute time and keeps the earliest.
   */
  public function testRecordStoresEarliestFlip(): void {
    $tracker = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $tracker->record(['node:1'], 600);
    $this->assertSame($this->flipAt(600), (int) $this->storedFlips()['node:1']);

    // A nearer flip for the same entity replaces the later one.
    $tracker->record(['node:1'], 60);
    $this->assertSame($this->flipAt(60), (int) $this->storedFlips()['node:1']);

    // A later flip does not push the stored time back out.
    $tracker->record(['node:1'], 900);
    $this->assertSame($this->flipAt(60), (int) $this->storedFlips()['node:1']);
  }

  /**
   * Tests only the entities whose flip time has passed are invalidated.
   */
  public function testInvalidatePassed(): void {
    $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class))
      ->record(['node:1'], 60);
    $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class))
      ->record(['node:2'], 600);

    // 120 seconds later, node:1 is due but node:2 is not.
    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);

    $tracker = $this->buildTracker(self::NOW + 120, $invalidator);
    $this->assertSame(1, $tracker->invalidatePassed());
    // The due row is removed; the future one is left.
    $flips = $this->storedFlips();
    $this->assertSame(['node:2'], array_keys($flips));
    $this->assertSame($this->flipAt(600), (int) $flips['node:2']);
  }

  /**
   * Tests invalidatePassed does nothing when no flip is due.
   */
  public function testInvalidateNothingDue(): void {
    $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class))
      ->record(['node:1'], 600);

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->never())->method('invalidateTags');

    $tracker = $this->buildTracker(self::NOW, $invalidator);
    $this->assertSame(0, $tracker->invalidatePassed());
    $this->assertArrayHasKey('node:1', $this->storedFlips());
  }

}
