<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\encrypt\EncryptService;
use Drupal\hs_bugherd\Form\BugherdConnectionSettingsForm;
use Drupal\Tests\UnitTestCase;

/**
 * Class BugherdConnectionSettingsFormTest
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionSettingsForm
 * @group hs_bugherd
 */
class BugherdConnectionSettingsFormTest extends UnitTestCase {

  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();

    $container->set('config.factory', $this->createMock(ConfigFactoryInterface::class));
    $container->set('encryption', $this->createMock(EncryptService::class));
    $container->set('form_builder', $this->createMock(FormBuilder::class));

    \Drupal::setContainer($container);
  }

  public function testSettingsForm() {
//    $form_object = BugherdConnectionSettingsForm::create(\Drupal::getContainer());
//    $this->assertEquals('bugherd_connection_settings_form', $form_object->getFormId());
//    $form = [];
//    $form_state = new FormState();
//    $form_object->buildForm($form, $form_state);
    $form = \Drupal::formBuilder()->getForm(BugherdConnectionSettingsForm::class);
    var_dump($form);
  }

}
