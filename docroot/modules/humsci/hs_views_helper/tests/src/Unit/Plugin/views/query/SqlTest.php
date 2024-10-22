<?php

namespace Drupal\Tests\hs_views_helper\Unit\Plugin\views\filter;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\field\FieldStorageConfigStorage;
use Drupal\hs_views_helper\Plugin\views\query\Sql;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\DateSqlInterface;
use Drupal\views\Plugin\ViewsPluginManager;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;

/**
 * Class SqlTest.
 *
 * @coversDefaultClass \Drupal\hs_views_helper\Plugin\views\query\Sql
 * @group hs_views_helper
 */
class SqlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new Container();
    $container->set('string_translation', $this->createMock(TranslationManager::class));

    $entity_storage = $this->createMock(FieldStorageConfigStorage::class);
    $entity_storage->method('load')->willReturn(new FakeFieldObject());

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Test aggregation info method.
   */
  public function testAggregationInfo() {
    $sql = $this->getSqlObject();
    $this->assertInstanceOf(Sql::class, $sql);
    $this->assertCount(10, $sql->getAggregationInfo());
  }

  /**
   * Test query alter functionality.
   */
  public function testQueryAlter() {
    $sql = $this->getSqlObject();
    $storage = $this->createMock(ViewEntityInterface::class);
    $user = $this->createMock(AccountInterface::class);
    $views_data = $this->createMock(ViewsData::class);
    $route_provider = $this->createMock(RouteProviderInterface::class);
    $display_plugin_manager = $this->createMock(ViewsPluginManager::class);
    $view = new ViewExecutable($storage, $user, $views_data, $route_provider, $display_plugin_manager);
    $sql->alterQuery($view);
    $this->assertEmpty($sql->orderby);
    $sql->fields = [
      'field_first' => [
        'table' => 'node__field_first',
        'field' => 'field_first',
      ],
      'field_second' => [
        'table' => 'node__field_second',
        'field' => 'field_second',
      ],
      'field_third' => [
        'table' => 'node__field_third',
        'field' => 'field_third',
      ],
      'created' => ['table' => 'node_field_data', 'field' => 'created'],
    ];
    $sql->orderby = [
      [
        'field' => 'field_first',
        'direction' => 'ASC',
      ],
      [
        'field' => 'field_second',
        'direction' => 'ASC',
      ],
      [
        'field' => 'field_third',
        'direction' => 'ASC',
      ],
      [
        'field' => 'field_fourth',
        'direction' => 'ASC',
      ],
      [
        'field' => 'created',
        'direction' => 'ASC',
      ],
    ];

    $sql->alterQuery($view);

    $this->assertCount(5, $sql->fields);
    $this->assertCount(3, $sql->orderby);
    $this->assertTrue(!empty($sql->fields['max_date']));
    $this->assertEquals('greatest', $sql->fields['max_date']['function']);

    $expression = 'COALESCE(node__field_first.field_first, 0), COALESCE(node__field_second.field_second, 0), COALESCE(node__field_third.field_third, 0)';
    $this->assertEquals($expression, $sql->fields['max_date']['field']);

    $this->assertEquals('max_date', $sql->orderby[0]['field']);
    $this->assertEquals('ASC', $sql->orderby[0]['direction']);
  }

  /**
   * Get the testable object.
   *
   * @return \Drupal\hs_views_helper\Plugin\views\query\Sql
   *   Testable object.
   */
  protected function getSqlObject() {
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $date_sql = $this->createMock(DateSqlInterface::class);
    $messenger = $this->createMock(Messenger::class);

    return new Sql([], '', [], $entity_type_manager, $date_sql, $messenger);
  }

}

/**
 * Class FakeFieldObject as if a field storage was returned.
 *
 * @package Drupal\Tests\hs_views_helper\Kernel\Plugin\views\filter
 */
class FakeFieldObject {

  /**
   * Return that this fake field is a date field.
   *
   * @return string
   *   Field type.
   */
  public function getType() {
    return 'datetime';
  }

}
