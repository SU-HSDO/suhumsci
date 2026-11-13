<?php

namespace Drupal\hs_blocks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

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
   * Field types that have a separate start and end a date rule can compare.
   */
  const DATE_RANGE_FIELD_TYPES = ['daterange', 'smartdate'];

  /**
   * The boundaries a date rule can compare on a field that has both.
   */
  const BOUNDARIES = ['start', 'end'];

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
   * Determines if a field has a separate start and end a date rule can compare.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if the field is a date range or event (smart date) field.
   */
  public static function hasDateRange(FieldDefinitionInterface $definition): bool {
    return in_array($definition->getType(), self::DATE_RANGE_FIELD_TYPES, TRUE);
  }

  /**
   * Resolves the entity whose layout is being displayed.
   *
   * Shared by the event subscriber and the manage form so both read the
   * context the same way.
   *
   * @param array $contexts
   *   The layout contexts, keyed by context name.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The entity, or NULL when there is no resolved fieldable entity.
   */
  public static function getContextEntity(array $contexts): ?FieldableEntityInterface {
    $context = $contexts['layout_builder.entity'] ?? NULL;
    // A present context can still be unresolved, so getContextValue() is only
    // safe to call once hasContextValue() confirms it holds an entity.
    $entity = $context && $context->hasContextValue() ? $context->getContextValue() : NULL;
    return $entity instanceof FieldableEntityInterface ? $entity : NULL;
  }

  /**
   * Determines if the rule hides the block for the given entity.
   *
   * @param array $rule
   *   The visibility rule, with `field` and `operator` keys, plus an optional
   *   `boundary` (`start` or `end`) for date rules on fields that have both.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity the rule is evaluated against.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the cache metadata the result depends on.
   * @param int|null $date_flip
   *   Receives when a date rule next changes the result (a Unix timestamp), or
   *   stays NULL. Just the date's own flip, so nothing spurious is scheduled.
   *
   * @return bool
   *   TRUE if the block should be hidden.
   */
  public function isHidden(array $rule, FieldableEntityInterface $entity, CacheableMetadata $metadata, ?int &$date_flip = NULL): bool {
    // Rebuild when the content changes, even if the rule is skipped below.
    $metadata->addCacheableDependency($entity);

    $field_name = $rule['field'] ?? '';
    $operator = $rule['operator'] ?? '';
    if (
      !in_array($operator, [...self::VALUE_OPERATORS, ...self::DATE_OPERATORS], TRUE) ||
      !is_string($field_name) ||
      !$entity->hasField($field_name)
    ) {
      // Fail open so a stale rule never removes content from the page.
      return FALSE;
    }

    $items = $entity->get($field_name);
    if (in_array($operator, self::VALUE_OPERATORS, TRUE)) {
      $is_empty = $this->isFieldEmpty($items, $metadata);
      return $operator === 'empty' ? $is_empty : !$is_empty;
    }

    if (!self::isDateField($items->getFieldDefinition())) {
      return FALSE;
    }
    // Default to the end, so old rules and single-value fields are unchanged.
    $boundary = in_array($rule['boundary'] ?? NULL, self::BOUNDARIES, TRUE) ? $rule['boundary'] : 'end';
    return $this->isDateMatch($operator, $items, $metadata, $boundary, $date_flip);
  }

  /**
   * Determines if a field is empty as the visitor would see it.
   *
   * Unlike isEmpty(), a field the visitor cannot view counts as empty (so a
   * rule can't reveal a hidden value), and a reference to only missing or
   * unpublished entities counts as empty (so the block isn't an empty shell).
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the cache metadata the access checks depend on.
   *
   * @return bool
   *   TRUE if the field holds nothing the visitor would see.
   */
  protected function isFieldEmpty(FieldItemListInterface $items, CacheableMetadata $metadata): bool {
    $access = $items->access('view', NULL, TRUE);
    $metadata->addCacheableDependency($access);
    if (!$access->isAllowed() || $items->isEmpty()) {
      return TRUE;
    }

    if ($items instanceof EntityReferenceFieldItemListInterface) {
      foreach ($items->referencedEntities() as $referenced_entity) {
        $referenced_access = $referenced_entity->access('view', NULL, TRUE);
        $metadata->addCacheableDependency($referenced_access);
        $metadata->addCacheableDependency($referenced_entity);
        if ($referenced_access->isAllowed()) {
          return FALSE;
        }
      }
      return TRUE;
    }

    return FALSE;
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
   * @param string $boundary
   *   Which end of a date range or event to compare, `start` or `end`.
   * @param int|null $date_flip
   *   Receives the nearest upcoming date as a Unix timestamp, if any.
   *
   * @return bool
   *   TRUE if the operator matches.
   */
  protected function isDateMatch(string $operator, FieldItemListInterface $items, CacheableMetadata $metadata, string $boundary, ?int &$date_flip = NULL): bool {
    $timestamps = $this->getTimestamps($items, $metadata, $boundary);
    if (!$timestamps) {
      // Nothing to compare, so don't hide.
      return FALSE;
    }

    $now = $this->time->getRequestTime();
    $upcoming = array_filter($timestamps, fn (int $timestamp) => $timestamp > $now);
    if ($upcoming) {
      // Flip when the nearest upcoming date passes. Merge (never replace) so a
      // shorter lifetime from elsewhere is kept.
      $date_flip = min($upcoming);
      $metadata->setCacheMaxAge(Cache::mergeMaxAges($metadata->getCacheMaxAge(), $date_flip - $now));
    }

    // With several values, "future" = any still ahead; "past" is the opposite.
    return $operator === 'future' ? !empty($upcoming) : empty($upcoming);
  }

  /**
   * Converts the field values into the timestamps they are compared by.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The date field values.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the site timezone config dependency.
   * @param string $boundary
   *   Which end of a date range or event to compare, `start` or `end`.
   *
   * @return int[]
   *   The timestamps.
   */
  protected function getTimestamps(FieldItemListInterface $items, CacheableMetadata $metadata, string $boundary): array {
    $config = $this->configFactory->get('system.date');
    $metadata->addCacheableDependency($config);
    $site_timezone = new \DateTimeZone($config->get('timezone.default') ?: 'UTC');

    $type = $items->getFieldDefinition()->getType();
    // Only range and smart date fields have a start and end; others use their
    // one value for either boundary.
    $use_end = $boundary !== 'start';
    $timestamps = [];
    foreach ($items as $item) {
      $item_value = $item->getValue();
      if ($type === 'timestamp' || $type === 'smartdate') {
        // Timestamp has one value; smart date has a start and end, both timed.
        $key = ($type === 'smartdate' && $use_end) ? 'end_value' : 'value';
        if (isset($item_value[$key]) && $item_value[$key] !== '') {
          $timestamps[] = (int) $item_value[$key];
        }
        continue;
      }

      // Datetime has one value; a date range stores the start and the end.
      $is_range_end = $type === 'daterange' && $use_end;
      $value = $item_value[$is_range_end ? 'end_value' : 'value'] ?? NULL;
      if (empty($value)) {
        continue;
      }

      // Only datetime and daterange reach here; datetime_type says if there's a
      // time.
      $is_date_only = $items->getFieldDefinition()->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE;
      if ($is_date_only) {
        // Date-only values start at midnight site time; a range end lasts to
        // the day's end.
        $date = \DateTimeImmutable::createFromFormat('!' . DateTimeItemInterface::DATE_STORAGE_FORMAT, $value, $site_timezone);
        if ($date && $is_range_end) {
          $date = $date->modify('+1 day');
        }
      }
      else {
        $date = \DateTimeImmutable::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $value, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
      }
      if ($date) {
        $timestamps[] = $date->getTimestamp();
      }
    }
    return $timestamps;
  }

}
