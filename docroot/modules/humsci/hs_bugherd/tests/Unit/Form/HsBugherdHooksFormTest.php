<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\hs_bugherd\Form\HsBugherdHooksForm;
use Drupal\Tests\hs_bugherd\Unit\HsBugherdUnitTestBase;

/**
 * Class HsBugherdHooksFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\HsBugherdHooksForm
 * @group hs_bugherd
 */
class HsBugherdHooksFormTest extends HsBugherdUnitTestBase {

  /**
   * Test hooks form.
   */
  public function testBugherdHooksForm() {
    $form_object = HsBugherdHooksForm::create($this->container);
    $this->assertEquals('hs_bugherd_hooks', $form_object->getFormId());
    $this->assertEquals('Rebuild All Webhooks?', $form_object->getQuestion()
      ->getUntranslatedString());
  }

}
