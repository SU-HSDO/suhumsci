<?php

namespace Drupal\Tests\hs_bugherd\Unit\Plugin\rest\resource;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\hs_bugherd\Plugin\rest\resource\BugherdResource;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Serializer;

/**
 * Class BugherdResourceBaseTest.
 *
 * @covers \Drupal\hs_bugherd\Plugin\rest\resource\BugherdResource
 * @covers \Drupal\hs_bugherd\Plugin\rest\resource\BugherdResourceBase
 * @group hs_bugherd
 */
class BugherdResourceTest extends UnitTestCase {

  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->setDefinition('serializer', new Definition(Serializer::class, [[], []]));

    \Drupal::setContainer($container);
  }

  public function testResourceBase() {
    $resource = BugherdResource::create(\Drupal::getContainer(), [], '', []);
  }

}
