<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\Form\BugherdConnectionForm;
use Drupal\Tests\hs_bugherd\Unit\HsBugherdUnitTestBase;

/**
 * Class BugherdConnectionFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionForm
 * @group hs_bugherd
 */
class BugherdConnectionFormTest extends HsBugherdUnitTestBase {

  protected function setUp() {
    parent::setUp();

    $this->container = \Drupal::getContainer();
    $this->container->set('entity_type.bundle.info', $this->createMock(EntityTypeBundleInfo::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Test Entity connection form methods.
   */
  public function testConnectionForm() {
    $form_object = BugherdConnectionForm::create($this->container);

    $form_object->setModuleHandler(\Drupal::moduleHandler());
    $form_object->setEntity(new TestBugherdConnection(['bugherdProject' => 123], 'bugherd_connection'));
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state);
    $this->assertCount(7, Element::children($form));

    $form_state->setValue('bugherdProject', 123);
    $url_element = $form_object->updateProjectUrls($form, $form_state);
    $this->assertEquals('http://example.com', $url_element['#default_value']);

    $form_state->setValues([
      'id' => $this->randomMachineName(),
      'label' => $this->getRandomGenerator()->string(),
    ]);
    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);
  }

}

/**
 * Class TestBugherdconnection.
 *
 * @package Drupal\Tests\hs_bugherd\Form
 */
class TestBugherdconnection extends BugherdConnection {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'edit-form', array $options = []) {
    return Url::fromRoute('admin');
  }

}