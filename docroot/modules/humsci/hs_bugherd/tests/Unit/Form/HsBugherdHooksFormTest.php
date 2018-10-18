<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\hs_bugherd\Form\HsBugherdHooksForm;
use Drupal\Tests\hs_bugherd\Unit\HsBugherdUnitTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HsBugherdHooksFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\HsBugherdHooksForm
 * @group hs_bugherd
 */
class HsBugherdHooksFormTest extends HsBugherdUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container = \Drupal::getContainer();
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn(new Request());
    $this->container->set('request_stack', $request_stack);
    $this->container->set('logger.factory', new LoggerChannelFactory());
    $this->container->set('cache_tags.invalidator', $this->createMock(CacheTagsInvalidator::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Test hooks form.
   */
  public function testBugherdHooksForm() {
    $form_object = HsBugherdHooksForm::create($this->container);
    $this->assertEquals('hs_bugherd_hooks', $form_object->getFormId());
    $this->assertEquals('Rebuild All Webhooks?', $form_object->getQuestion()
      ->getUntranslatedString());
    $form = $form_object->buildForm([], new FormState());

    $form_object->submitForm($form, new FormState());
  }

}
