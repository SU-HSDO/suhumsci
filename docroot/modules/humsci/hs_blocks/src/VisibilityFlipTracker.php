<?php

namespace Drupal\hs_blocks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;

/**
 * Updates date-based blocks behind Varnish once their date passes.
 *
 * A date rule shortens a block's cache life. Drupal honors that, but Acquia's
 * Varnish does not and only clears on a cache-tag invalidation. So we note when
 * each block next changes and let cron clear its cache tag then. The notes are
 * gathered in memory and saved once by flush().
 */
class VisibilityFlipTracker {

  /**
   * The table storing pending flips, keyed by cache tag.
   */
  const TABLE = 'hs_blocks_visibility_flip';

  /**
   * Flips noted this request, keyed by cache tag.
   *
   * @var array<string, int>
   */
  protected array $pending = [];

  /**
   * Constructs a new VisibilityFlipTracker.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected Connection $database,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected TimeInterface $time,
  ) {
  }

  /**
   * Notes, in memory, when a block's date rule next changes. flush() saves it.
   *
   * @param string[] $cache_tags
   *   Cache tags of the entity the rule checks.
   * @param int $flip_time
   *   When the rule next changes, as a Unix timestamp. Ignored if not upcoming.
   */
  public function record(array $cache_tags, int $flip_time): void {
    if ($flip_time <= $this->time->getRequestTime()) {
      return;
    }

    foreach ($cache_tags as $cache_tag) {
      // Keep the earliest flip when several blocks target one entity.
      if (!isset($this->pending[$cache_tag]) || $flip_time < $this->pending[$cache_tag]) {
        $this->pending[$cache_tag] = $flip_time;
      }
    }
  }

  /**
   * Saves the flips noted this request. Runs once, after the response is sent.
   */
  public function flush(): void {
    foreach ($this->pending as $cache_tag => $flip_time) {
      $this->database->merge(self::TABLE)
        ->keys(['cache_tag' => $cache_tag])
        ->fields(['flip_time' => $flip_time])
        // Keep the earliest flip: clearing a bit early is harmless, clearing
        // too late would leave a page stale.
        ->expression('flip_time', 'LEAST([flip_time], :flip_time)', [':flip_time' => $flip_time])
        ->execute();
    }
    $this->pending = [];
  }

  /**
   * Invalidates the cache tags whose flip time has passed.
   *
   * @return int
   *   The number of cache tags invalidated.
   */
  public function invalidatePassed(): int {
    $now = $this->time->getRequestTime();
    $cache_tags = $this->database->select(self::TABLE, 'f')
      ->fields('f', ['cache_tag'])
      ->condition('flip_time', $now, '<=')
      ->execute()
      ->fetchCol();
    if (!$cache_tags) {
      return 0;
    }

    // Clearing the entity's cache tag purges its pages, Varnish included. The
    // next visit rebuilds the page and notes its next flip, if any.
    $this->cacheTagsInvalidator->invalidateTags($cache_tags);
    $this->database->delete(self::TABLE)
      ->condition('flip_time', $now, '<=')
      ->execute();
    return count($cache_tags);
  }

}
