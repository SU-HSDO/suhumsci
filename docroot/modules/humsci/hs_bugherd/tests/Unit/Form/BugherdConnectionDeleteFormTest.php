<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Form\FormState;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\Form\BugherdConnectionDeleteForm;
use Drupal\Tests\hs_bugherd\Unit\HsBugherdUnitTestBase;

/**
 * Class BugherdConnectionDeleteFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionDeleteForm
 * @group hs_bugherd
 */
class BugherdConnectionDeleteFormTest extends HsBugherdUnitTestBase {

  /**
   * Test Delete form methods.
   */
  public function testBugherdConnectionDeleteForm() {
    $form_object = BugherdConnectionDeleteForm::create($this->container);
    $form_object->setEntity(new BugherdConnection([
      'id' => $this->randomMachineName(),
      'label' => $this->getRandomGenerator()->string(),
    ], 'bugherd_connection'));

    $this->assertEquals('Are you sure you want to delete %name?', $form_object->getQuestion()
      ->getUntranslatedString());
    $this->assertEquals('Delete', $form_object->getConfirmText()
      ->getUntranslatedString());
    $this->assertEquals('route:entity.bugherd_connection.collection', $form_object->getCancelUrl()
      ->toUriString());
    $form = $form_object->form([], new FormState());
    $form_object->submitForm($form, new FormState());
  }

}
