<?php

namespace Drupal\Tests\hs_views_helper\Unit\Plugin\Block;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\hs_blocks\Plugin\Block\HsLoginBlock;
use Drupal\hs_views_helper\Normalizer\HumsciMarkupNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NormalizerTest.
 *
 * @group hs_views_helper
 * @coversDefaultClass \Drupal\hs_views_helper\Normalizer\HumsciMarkupNormalizer
 */
class HumsciMarkupNormalizerTest extends UnitTestCase {

  /**
   * Test functioning normalizer.
   */
  public function testNormalizer() {
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $normalizer = new HumsciMarkupNormalizer($logger_factory);

    $this->assertInstanceOf(HumsciMarkupNormalizer::class, $normalizer);
    $object = '
<div data-attribute-tag="newTag">one</div>
<div data-attribute-tag="newTag">two</div>
<div data-attribute-tag="newTag">three</div>';
    $normalized = $normalizer->normalize($object);

    $converted_object = ['newTag' => ['one', 'two', 'three']];
    $this->assertEquals($converted_object, $normalized);
  }

  /**
   * Test when the object isn't valid html.
   */
  public function testErrorNormalizer() {
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')
      ->willReturn($this->createMock(LoggerChannelInterface::class));

    $normalizer = new HumsciMarkupNormalizer($logger_factory);

    $object = '<root data-attribute-tag="newTag">one</root>';
    $normalized = $normalizer->normalize($object);
    $this->assertEquals($object, $normalized);
  }

}
