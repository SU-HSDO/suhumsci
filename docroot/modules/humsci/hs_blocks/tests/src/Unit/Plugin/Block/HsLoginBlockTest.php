<?php

namespace Drupal\Tests\hs_blocks\Unit\Plugin\Block;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\UserSession;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\hs_blocks\Plugin\Block\HsLoginBlock;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HsLoginBlockTest.
 *
 * @covers \Drupal\hs_blocks\Plugin\Block\HsLoginBlock
 * @group hs_blocks
 */
class HsLoginBlockTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    //    $request = new Request();
    //    $request->getPathInfo()

    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn(new Request());
    $container->set('request_stack', $request_stack);

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')
      ->with('simplesamlphp_auth')
      ->willReturn(TRUE);
    $container->set('module_handler', $module_handler);
    $string_translation = $this->createMock(TranslationManager::class);
    $container->set('string_translation', $string_translation);

    \Drupal::setContainer($container);
  }

  /**
   * Test the login block gives the correct render array.
   */
  public function testLoginBlock() {
    $configuration = [];
    $definition = ['provider' => 'hs_blocks'];
    $block = HsLoginBlock::create(\Drupal::getContainer(), $configuration, 'hs_login_block', $definition);

    $this->assertArrayHasKey('link_text', $block->defaultConfiguration());
    $this->assertArrayHasKey('preface', $block->defaultConfiguration());

    $form = [];
    $form_state = new FormState();
    $form = $block->blockForm($form, $form_state);
    $this->assertArrayHasKey('preface', $form);
    $this->assertArrayHasKey('link_text', $form);
    $this->assertArrayHasKey('#attached', $form);

    $form_state->setValue('preface', [
      'value' => 'Testing 123',
      'format' => 'full_html',
    ]);
    $form_state->setValue('link_text', 'Log in here');
    $block->blockSubmit($form, $form_state);
    $new_config = $block->getConfiguration();
    $this->assertEquals('Testing 123', $new_config['preface']['value']);
    $this->assertEquals('full_html', $new_config['preface']['format']);
    $this->assertEquals('Log in here', $new_config['link_text']);

    $build = $block->build();
    $this->assertArrayHasKey('#preface', $build);
    $this->assertArrayHasKey('#link', $build);

    $account = new UserSession();
    $this->assertTrue($block->access($account));

    $account = new UserSession(['uid' => 1]);
    $this->assertFalse($block->access($account));
  }

}
