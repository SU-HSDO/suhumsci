<?php

namespace Drupal\Tests\hs_blocks\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\hs_blocks\VisibilityRuleEvaluator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the visibility rule evaluator.
 */
#[CoversClass(VisibilityRuleEvaluator::class)]
#[Group('hs_blocks')]
class VisibilityRuleEvaluatorTest extends UnitTestCase {

  /**
   * Builds a mocked entity with a single field.
   *
   * @param bool $field_exists
   *   Whether the entity has the field.
   * @param bool $field_empty
   *   Whether the field is empty.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The mocked entity.
   */
  protected function buildEntity(bool $field_exists, bool $field_empty): FieldableEntityInterface {
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('isEmpty')->willReturn($field_empty);
    $items->method('access')->willReturn(AccessResult::allowed());
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn('string');
    $items->method('getFieldDefinition')->willReturn($definition);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->with('field_title')->willReturn($field_exists);
    $entity->method('get')->with('field_title')->willReturn($items);
    $entity->method('getCacheTags')->willReturn(['node:1']);
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);
    return $entity;
  }

  /**
   * Builds the evaluator with stubbed services.
   *
   * @return \Drupal\hs_blocks\VisibilityRuleEvaluator
   *   The evaluator.
   */
  protected function buildEvaluator(): VisibilityRuleEvaluator {
    return new VisibilityRuleEvaluator(
      $this->createMock(TimeInterface::class),
      $this->createMock(ConfigFactoryInterface::class)
    );
  }

  /**
   * Tests every operator against empty and filled fields.
   */
  #[DataProvider('providerIsHidden')]
  public function testIsHidden(array $rule, bool $field_exists, bool $field_empty, bool $expected): void {
    $evaluator = $this->buildEvaluator();
    $metadata = new CacheableMetadata();

    $entity = $this->buildEntity($field_exists, $field_empty);
    $this->assertSame($expected, $evaluator->isHidden($rule, $entity, $metadata));
  }

  /**
   * Data provider for ::testIsHidden().
   */
  public static function providerIsHidden(): array {
    $empty = ['field' => 'field_title', 'operator' => 'empty'];
    $not_empty = ['field' => 'field_title', 'operator' => 'not_empty'];
    return [
      'empty rule, empty field' => [$empty, TRUE, TRUE, TRUE],
      'empty rule, filled field' => [$empty, TRUE, FALSE, FALSE],
      'not_empty rule, filled field' => [$not_empty, TRUE, FALSE, TRUE],
      'not_empty rule, empty field' => [$not_empty, TRUE, TRUE, FALSE],
      'missing field fails open' => [$empty, FALSE, TRUE, FALSE],
      'unknown operator fails open' => [['operator' => 'bogus'] + $empty, TRUE, TRUE, FALSE],
      'date operator on a non-date field fails open' => [['operator' => 'future'] + $empty, TRUE, TRUE, FALSE],
      'incomplete rule fails open' => [['field' => 'field_title'], TRUE, TRUE, FALSE],
    ];
  }

  /**
   * Tests the entity cache tags are always collected.
   */
  public function testEntityCacheTagsAreAdded(): void {
    $evaluator = $this->buildEvaluator();
    $metadata = new CacheableMetadata();

    $entity = $this->buildEntity(FALSE, TRUE);
    $evaluator->isHidden([], $entity, $metadata);
    $this->assertSame(['node:1'], $metadata->getCacheTags());
  }

  /**
   * Tests a field the visitor cannot view counts as empty.
   */
  public function testInaccessibleFieldIsEmpty(): void {
    $items = $this->createMock(FieldItemListInterface::class);
    // The field is filled, but the visitor cannot view it, so it renders as
    // nothing and must not reveal that it has a value.
    $items->method('isEmpty')->willReturn(FALSE);
    $items->method('access')->willReturn(AccessResult::forbidden());
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn('string');
    $items->method('getFieldDefinition')->willReturn($definition);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($items);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $evaluator = $this->buildEvaluator();
    $this->assertTrue($evaluator->isHidden(['field' => 'field_title', 'operator' => 'empty'], $entity, new CacheableMetadata()));
    $this->assertFalse($evaluator->isHidden(['field' => 'field_title', 'operator' => 'not_empty'], $entity, new CacheableMetadata()));
  }

}
