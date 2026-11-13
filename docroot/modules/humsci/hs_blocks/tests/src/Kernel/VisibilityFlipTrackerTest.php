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
   * Tests a flip at or before now is never scheduled.
   */
  public function testNothingRecordedWithoutUpcomingFlip(): void {
    $tracker = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $tracker->record(['node:1'], self::NOW - 1);
    $tracker->record(['node:1'], self::NOW);
    $tracker->flush();
    $this->assertEmpty($this->storedFlips());
  }

  /**
   * Tests nothing is written until the request is flushed.
   */
  public function testRecordDefersTheWrite(): void {
    $tracker = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $tracker->record(['node:1'], $this->flipAt(600));
    $before_flush = $this->storedFlips();
    $this->assertEmpty($before_flush);
    $tracker->flush();
    $this->assertSame($this->flipAt(600), (int) $this->storedFlips()['node:1']);
  }

  /**
   * Tests the earliest flip is kept, both within a request and across writes.
   */
  public function testRecordStoresEarliestFlip(): void {
    // The earliest of several flips for one entity in a request wins.
    $tracker = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $tracker->record(['node:1'], $this->flipAt(600));
    $tracker->record(['node:1'], $this->flipAt(60));
    $tracker->record(['node:1'], $this->flipAt(900));
    $tracker->flush();
    $this->assertSame($this->flipAt(60), (int) $this->storedFlips()['node:1']);

    // A later flip written by another request does not push the stored one out.
    $later = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $later->record(['node:1'], $this->flipAt(900));
    $later->flush();
    $this->assertSame($this->flipAt(60), (int) $this->storedFlips()['node:1']);
  }

  /**
   * Tests only the entities whose flip time has passed are invalidated.
   */
  public function testInvalidatePassed(): void {
    $first = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $first->record(['node:1'], $this->flipAt(60));
    $first->flush();
    $second = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $second->record(['node:2'], $this->flipAt(600));
    $second->flush();

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
    $seed = $this->buildTracker(self::NOW, $this->createMock(CacheTagsInvalidatorInterface::class));
    $seed->record(['node:1'], $this->flipAt(600));
    $seed->flush();

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->never())->method('invalidateTags');

    $tracker = $this->buildTracker(self::NOW, $invalidator);
    $this->assertSame(0, $tracker->invalidatePassed());
    $this->assertArrayHasKey('node:1', $this->storedFlips());
  }

}
