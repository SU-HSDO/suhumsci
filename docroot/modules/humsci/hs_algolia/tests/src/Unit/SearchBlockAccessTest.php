<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\hs_algolia\SearchStatus;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests hiding the Drupal search form above Algolia results.
 */
#[Group('hs_algolia')]
class SearchBlockAccessTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
  }

  /**
   * Register a search status service.
   */
  protected function setUpStatus(bool $active): void {
    $status = $this->createMock(SearchStatus::class);
    $status->method('isActive')->willReturn($active);
    $status->method('getCacheableMetadata')->willReturn(
      (new CacheableMetadata())->addCacheTags(['config:hs_algolia.settings'])
    );
    $container = new ContainerBuilder();
    $container->set('hs_algolia.search_status', $status);
    \Drupal::setContainer($container);
  }

  /**
   * Build a block entity.
   */
  protected function mockBlock(string $plugin_id, string $region): BlockInterface {
    $block = $this->createMock(BlockInterface::class);
    $block->method('getPluginId')->willReturn($plugin_id);
    $block->method('getRegion')->willReturn($region);
    return $block;
  }

  /**
   * The in-page Drupal form is hidden only while Algolia serves the page.
   */
  public function testInPageFormHiddenWhenActive(): void {
    $block = $this->mockBlock(HS_ALGOLIA_SEARCH_FORM_BLOCK, 'content');
    $account = $this->createMock(AccountInterface::class);

    $this->setUpStatus(TRUE);
    $result = hs_algolia_block_access($block, 'view', $account);
    $this->assertTrue($result->isForbidden());
    $this->assertInstanceOf(AccessResult::class, $result);
    $this->assertContains('config:hs_algolia.settings', $result->getCacheTags());

    $this->setUpStatus(FALSE);
    $this->assertFalse(hs_algolia_block_access($block, 'view', $account)->isForbidden());
  }

  /**
   * The header search box and every other block are left alone.
   */
  public function testOtherBlocksUntouched(): void {
    $this->setUpStatus(TRUE);
    $account = $this->createMock(AccountInterface::class);

    foreach ([
      $this->mockBlock(HS_ALGOLIA_SEARCH_FORM_BLOCK, HS_ALGOLIA_HEADER_SEARCH_REGION),
      $this->mockBlock('system_main_block', 'content'),
    ] as $block) {
      $this->assertTrue(hs_algolia_block_access($block, 'view', $account)->isNeutral());
    }

    $block = $this->mockBlock(HS_ALGOLIA_SEARCH_FORM_BLOCK, 'content');
    $this->assertTrue(hs_algolia_block_access($block, 'update', $account)->isNeutral());
  }

}
