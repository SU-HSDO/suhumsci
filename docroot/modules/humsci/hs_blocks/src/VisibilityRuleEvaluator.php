<?php

namespace Drupal\hs_blocks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Evaluates a block visibility rule against a field on an entity.
 */
class VisibilityRuleEvaluator {

  /**
   * Operators that match when the field is empty or not empty.
   */
  const VALUE_OPERATORS = ['empty', 'not_empty'];

  /**
   * Operators that compare the field's date to the current time.
   */
  const DATE_OPERATORS = ['future', 'past'];

  /**
   * Field types the date operators can be used with.
   */
  const DATE_FIELD_TYPES = ['datetime', 'daterange', 'smartdate', 'timestamp'];

  /**
   * Constructs a new VisibilityRuleEvaluator.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(protected TimeInterface $time, protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * Determines if a field definition supports the date operators.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if the field is a date field.
   */
  public static function isDateField(FieldDefinitionInterface $definition): bool {
    return in_array($definition->getType(), self::DATE_FIELD_TYPES, TRUE);
  }

  /**
   * Determines if the rule hides the block for the given entity.
   *
   * @param array $rule
   *   The visibility rule, with `field`, `operator` and `action` keys.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity the rule is evaluated against.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the cache metadata the result depends on.
   *
   * @return bool
   *   TRUE if the block should be hidden.
   */
  public function isHidden(array $rule, FieldableEntityInterface $entity, CacheableMetadata $metadata): bool {
    // Changing the field value must invalidate the block, even when the rule
    // is skipped below, because the rule or the field may be fixed later.
    $metadata->addCacheableDependency($entity);

    $field_name = $rule['field'] ?? '';
    $operator = $rule['operator'] ?? '';
    if (
      ($rule['action'] ?? '') !== 'hide' ||
      !in_array($operator, [...self::VALUE_OPERATORS, ...self::DATE_OPERATORS], TRUE) ||
      !is_string($field_name) ||
      !$entity->hasField($field_name)
    ) {
      // Fail open so a stale rule never removes content from the page.
      return FALSE;
    }

    $items = $entity->get($field_name);
    if (in_array($operator, self::VALUE_OPERATORS, TRUE)) {
      $is_empty = $items->isEmpty();
      return $operator === 'empty' ? $is_empty : !$is_empty;
    }

    if (!self::isDateField($items->getFieldDefinition())) {
      return FALSE;
    }
    return $this->isDateMatch($operator, $items, $metadata);
  }

  /**
   * Compares the dates of a field to the current time.
   *
   * @param string $operator
   *   Either `future` or `past`.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The date field values.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the max-age, so the result is rebuilt when the date passes.
   *
   * @return bool
   *   TRUE if the operator matches.
   */
  protected function isDateMatch(string $operator, FieldItemListInterface $items, CacheableMetadata $metadata): bool {
    $timestamps = $this->getTimestamps($items, $metadata);
    if (!$timestamps) {
      // Nothing to compare, so don't hide.
      return FALSE;
    }

    $now = $this->time->getRequestTime();
    $upcoming = array_filter($timestamps, fn (int $timestamp) => $timestamp > $now);
    if ($upcoming) {
      // Both operators flip at the moment the nearest upcoming date passes.
      $metadata->setCacheMaxAge(min($upcoming) - $now);
    }

    // With several values, "future" means any value is still ahead, which
    // makes "past" the exact opposite.
    return $operator === 'future' ? !empty($upcoming) : empty($upcoming);
  }

  /**
   * Converts the field values into the timestamps they are compared by.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The date field values.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the site timezone config dependency.
   *
   * @return int[]
   *   The timestamps.
   */
  protected function getTimestamps(FieldItemListInterface $items, CacheableMetadata $metadata): array {
    $config = $this->configFactory->get('system.date');
    $metadata->addCacheableDependency($config);
    $site_timezone = new \DateTimeZone($config->get('timezone.default') ?: 'UTC');

    $type = $items->getFieldDefinition()->getType();
    $timestamps = [];
    foreach ($items as $item) {
      $item_value = $item->getValue();
      if ($type === 'timestamp' || $type === 'smartdate') {
        // Smart date stores both ends as timestamps, and an event is over when
        // its end is.
        $key = $type === 'smartdate' ? 'end_value' : 'value';
        if (isset($item_value[$key]) && $item_value[$key] !== '') {
          $timestamps[] = (int) $item_value[$key];
        }
        continue;
      }

      // An event is over when its end date is, so ranges use the end.
      $is_end = $type === 'daterange';
      $value = $item_value[$is_end ? 'end_value' : 'value'] ?? NULL;
      if (empty($value)) {
        continue;
      }

      // Date-only values have no time or timezone, so they start at midnight
      // in the site timezone. As an end, the date lasts until the day is over.
      if (strlen($value) === 10) {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $site_timezone);
        if ($date && $is_end) {
          $date = $date->modify('+1 day');
        }
      }
      else {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value, new \DateTimeZone('UTC'));
      }
      if ($date) {
        $timestamps[] = $date->getTimestamp();
      }
    }
    return $timestamps;
  }

}
