<?php

namespace Drupal\Tests\hs_blocks\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_blocks\VisibilityRuleEvaluator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the date operators against real date fields.
 */
#[CoversClass(VisibilityRuleEvaluator::class)]
#[Group('hs_blocks')]
#[RunTestsInSeparateProcesses]
class VisibilityRuleEvaluatorDatesTest extends KernelTestBase {

  /**
   * The fixed current time: 2030-06-15 12:00:00 UTC (05:00 in Los Angeles).
   */
  const NOW = 1907755200;

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
    'datetime_range',
    'smart_date',
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
    $this->config('system.date')->set('timezone.default', 'America/Los_Angeles')->save();
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    $fields = [
      'field_date' => ['datetime', ['datetime_type' => 'date']],
      'field_datetime' => ['datetime', ['datetime_type' => 'datetime']],
      'field_range' => ['daterange', ['datetime_type' => 'date']],
      'field_stamp' => ['timestamp', []],
      'field_smart' => ['smartdate', []],
      'field_multi' => ['datetime', ['datetime_type' => 'datetime']],
    ];
    foreach ($fields as $name => [$type, $settings]) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'settings' => $settings,
        'cardinality' => $name === 'field_multi' ? -1 : 1,
      ])->save();
      FieldConfig::create(['field_name' => $name, 'entity_type' => 'node', 'bundle' => 'page'])->save();
    }
  }

  /**
   * Evaluates a rule for a node at the fixed time.
   *
   * @param string $field
   *   The field name.
   * @param string $operator
   *   The operator.
   * @param mixed $value
   *   The field value.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Receives the cache metadata.
   *
   * @return bool
   *   TRUE if the block is hidden.
   */
  protected function isHidden(string $field, string $operator, mixed $value, CacheableMetadata $metadata): bool {
    $time = $this->createStub(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::NOW);
    $evaluator = new VisibilityRuleEvaluator($time, $this->container->get('config.factory'));

    $node = Node::create(['type' => 'page', 'title' => 'Test', $field => $value]);
    $node->save();
    return $evaluator->isHidden(['field' => $field, 'operator' => $operator, 'action' => 'hide'], $node, $metadata);
  }

  /**
   * Tests the operators and the max-age for each kind of date field.
   *
   * @param int|null $max_age
   *   The expected max-age, or NULL for permanent.
   */
  #[DataProvider('providerDates')]
  public function testDates(string $field, string $operator, mixed $value, bool $expected, ?int $max_age): void {
    $metadata = new CacheableMetadata();
    $this->assertSame($expected, $this->isHidden($field, $operator, $value, $metadata));
    $this->assertSame($max_age ?? -1, $metadata->getCacheMaxAge());
  }

  /**
   * Data provider for ::testDates().
   */
  public static function providerDates(): array {
    $future_datetime = '2030-06-15T13:00:00';
    return [
      'datetime in the future, hide future' => ['field_datetime', 'future', $future_datetime, TRUE, 3600],
      'datetime in the future, hide past' => ['field_datetime', 'past', $future_datetime, FALSE, 3600],
      'datetime in the past, hide past' => ['field_datetime', 'past', '2030-06-15T11:00:00', TRUE, NULL],
      'datetime in the past, hide future' => ['field_datetime', 'future', '2030-06-15T11:00:00', FALSE, NULL],
      'datetime equal to now is past' => ['field_datetime', 'past', '2030-06-15T12:00:00', TRUE, NULL],
      // 2030-06-15 05:00 in Los Angeles: a date-only value starts at local
      // midnight, so today is already past and tomorrow is 19 hours away.
      'date today is past' => ['field_date', 'past', '2030-06-15', TRUE, NULL],
      'date tomorrow is future' => ['field_date', 'future', '2030-06-16', TRUE, 19 * 3600],
      // A range lasts through its end date, so ending today is still future.
      'range ending today is still future' => ['field_range', 'future', ['value' => '2030-06-10', 'end_value' => '2030-06-15'], TRUE, 19 * 3600],
      'range ended yesterday is past' => ['field_range', 'past', ['value' => '2030-06-10', 'end_value' => '2030-06-14'], TRUE, NULL],
      'timestamp in the future' => ['field_stamp', 'future', self::NOW + 60, TRUE, 60],
      'timestamp in the past' => ['field_stamp', 'past', self::NOW - 60, TRUE, NULL],
      'smart date ending in the future' => ['field_smart', 'future', ['value' => self::NOW - 3600, 'end_value' => self::NOW + 1800], TRUE, 1800],
      'smart date that has ended' => ['field_smart', 'past', ['value' => self::NOW - 7200, 'end_value' => self::NOW - 3600], TRUE, NULL],
      'empty date is never hidden by future' => ['field_datetime', 'future', NULL, FALSE, NULL],
      'empty date is never hidden by past' => ['field_datetime', 'past', NULL, FALSE, NULL],
      'multiple values, one upcoming, nearest wins' => [
        'field_multi',
        'future',
        ['2030-06-15T11:00:00', '2030-06-15T14:00:00', '2030-06-15T13:00:00'],
        TRUE,
        3600,
      ],
      'multiple values, all past' => ['field_multi', 'past', ['2030-06-14T11:00:00', '2030-06-15T11:00:00'], TRUE, NULL],
    ];
  }

}
