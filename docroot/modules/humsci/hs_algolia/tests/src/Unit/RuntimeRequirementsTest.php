<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\search_api\IndexInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the status report warning for a Training Hub site without a domain.
 */
#[Group('hs_algolia')]
class RuntimeRequirementsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';
  }

  /**
   * Build a site whose Training Hub index and domain are given.
   *
   * @param bool|null $index_enabled
   *   Whether the Training Hub index is enabled, or NULL when it is missing.
   * @param string $domain
   *   Value of domain_301_redirect.settings:domain.
   */
  protected function setUpSite(?bool $index_enabled, string $domain): void {
    $index = NULL;
    if ($index_enabled !== NULL) {
      $index = $this->createMock(IndexInterface::class);
      $index->method('status')->willReturn($index_enabled);
    }

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(HS_ALGOLIA_FEDERATED_INDEX_ID)->willReturn($index);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);
    $repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $repository->method('getEntityTypeFromClass')->willReturn('search_api_index');

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('domain')->willReturn($domain);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('domain_301_redirect.settings')->willReturn($config);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $repository);
    $container->set('config.factory', $config_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * An enabled Training Hub index without a canonical domain warns.
   */
  public function testEnabledIndexWithoutDomainWarns(): void {
    $this->setUpSite(TRUE, '');

    $requirements = hs_algolia_runtime_requirements();

    $this->assertSame(RequirementSeverity::Warning, $requirements['hs_algolia_federated_domain']['severity']);
  }

  /**
   * Sites with a domain, or not on the Training Hub, report nothing.
   */
  public function testOtherSitesReportNothing(): void {
    foreach ([
      'domain set' => [TRUE, 'https://example.stanford.edu'],
      'index disabled' => [FALSE, ''],
      'index missing' => [NULL, ''],
    ] as $case => [$enabled, $domain]) {
      $this->setUpSite($enabled, $domain);
      $this->assertSame([], hs_algolia_runtime_requirements(), $case);
    }
  }

}
