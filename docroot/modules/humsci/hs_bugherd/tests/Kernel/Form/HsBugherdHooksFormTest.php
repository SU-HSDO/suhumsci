<?php

namespace Drupal\Tests\hs_bugherd\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\hs_bugherd\Form\HsBugherdHooksForm;
use Drupal\Tests\hs_bugherd\Kernel\HsBugherdTestBase;

/**
 * Class HsBugherdFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\HsBugherdHooksForm
 * @group hs_bugherd
 */
class HsBugherdHooksFormTest extends HsBugherdTestBase {

  /**
   * Test the webhooks form functionality.
   */
  public function testBugherdHooksForm() {
    $form_object = HsBugherdHooksForm::create($this->container);
    $this->assertEquals('hs_bugherd_hooks', $form_object->getFormId());
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $this->assertCount(4, Element::children($form));
    $this->assertArrayHasKey('jira', $form['hooks']);
    $this->assertArrayHasKey('bugherd', $form['hooks']);
    $this->assertEmpty($form['hooks']['jira']['hooks']['#markup']);
    $this->assertEmpty($form['hooks']['bugherd']['hooks']['#markup']);

    $form_object->submitForm($form, $form_state);

    $this->setServices(TRUE);
    $form_object = HsBugherdHooksForm::create($this->container);
    $form = $form_object->buildForm($form, $form_state);
    $this->assertEquals('jira:issue_updated; comment_created: http://example.com', $form['hooks']['jira']['hooks']['#markup']);
    $this->assertEquals('comment: http://example.com', $form['hooks']['bugherd']['hooks']['#markup']);

    $form_object->submitForm($form, $form_state);

    $this->config('bugherdapi.settings')->delete();
    $form = $form_object->buildForm($form, $form_state);
    $this->assertCount(0, Element::children($form));
    $this->assertEquals('Bugherd or Jira has not been configured.', $form['#markup']->render());
  }

}
