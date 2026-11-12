<?php

namespace Drupal\hs_blocks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;

/**
 * Tracks when date-based visibility rules change and invalidates them on cron.
 *
 * A date rule shortens a block's cache lifetime so the block flips when the
 * date passes. Drupal's own page and dynamic page caches honor that lifetime,
 * but Acquia's Varnish serves anonymous pages for the fixed page lifetime and
 * is only purged when a cache tag is invalidated. This records when each
 * affected entity's result changes as its page is built, so cron can
 * invalidate the entity's cache tag once the time passes, purging Varnish.
 */
class VisibilityFlipTracker {

  /**
   * The table storing pending flips, keyed by cache tag.
   */
  const TABLE = 'hs_blocks_visibility_flip';

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
   * Records when an entity's date-based rule will next change its result.
   *
   * @param string[] $cache_tags
   *   The cache tags of the entity the rule is evaluated against.
   * @param int $max_age
   *   The seconds until the result changes, as collected from the rule. A
   *   non-positive value means nothing upcoming changes the result, so nothing
   *   is recorded.
   */
  public function record(array $cache_tags, int $max_age): void {
    if ($max_age <= 0) {
      return;
    }

    $flip_time = $this->time->getRequestTime() + $max_age;
    foreach ($cache_tags as $cache_tag) {
      $this->database->merge(self::TABLE)
        ->keys(['cache_tag' => $cache_tag])
        ->fields(['flip_time' => $flip_time])
        // Keep the earliest pending flip when several rules target one entity.
        ->expression('flip_time', 'LEAST([flip_time], :flip_time)', [':flip_time' => $flip_time])
        ->execute();
    }
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

    // Invalidating the entity's cache tag purges the pages that depend on it,
    // including behind Acquia's Varnish layer. The next request rebuilds the
    // page and records the entity's next flip, if any.
    $this->cacheTagsInvalidator->invalidateTags($cache_tags);
    $this->database->delete(self::TABLE)
      ->condition('flip_time', $now, '<=')
      ->execute();
    return count($cache_tags);
  }

}
