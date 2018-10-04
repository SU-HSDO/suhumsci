<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\Form\BugherdConnectionSettingsForm;
use Drupal\Tests\hs_bugherd\Unit\HsBugherdUnitTestBase;

/**
 * Class BugherdConnectionSettingsFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionSettingsForm
 * @group hs_bugherd
 */
class BugherdConnectionSettingsFormTest extends HsBugherdUnitTestBase {

  /**
   * Test Settings form class.
   */
  public function testWorks() {
    $this->assertEquals(1, 1);
    $form_object = TestBugherdConnectionSettingsForm::create($this->container);
    $this->assertEquals('bugherd_connection_settings_form', $form_object->getFormId());
    $this->assertArrayEquals(['hs_bugherd.connection_settings'], $form_object->getEditableConfigNames());
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state);
    $this->assertArrayHasKey('api_key', $form);
    $this->assertCount(2, Element::children($form));
  }

}

/**
 * Class TestBugherdConnectionSettingsForm
 *
 * @package Drupal\Tests\hs_bugherd\Form
 */
class TestBugherdConnectionSettingsForm extends BugherdConnectionSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return parent::getEditableConfigNames();
  }

  /**
   * {@inheritdoc}
   */
  protected function config($name) {
    return new BugherdConnection([], 'bugherd_connection');
  }
}
