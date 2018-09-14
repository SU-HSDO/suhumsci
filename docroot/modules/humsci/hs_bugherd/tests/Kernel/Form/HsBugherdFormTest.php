<?php

namespace Drupal\Tests\hs_bugherd\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\hs_bugherd\Form\HsBugherdForm;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\key\Entity\Key;
use Drupal\Tests\hs_bugherd\Kernel\HsBugherdTestBase;

/**
 * Class HsBugherdFormTest.
 *
 * @covers \Drupal\hs_bugherd\Form\HsBugherdForm
 * @covers \Drupal\hs_bugherd\HsBugherd
 * @group hs_bugherd
 */
class HsBugherdFormTest extends HsBugherdTestBase {

  /**
   * Key entity.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $badKey;

  /**
   * {@inheritdo}
   */
  protected function setUp() {
    parent::setUp();

    $this->badKey = Key::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_input' => 'text_field',
      'key_provider_settings' => ['key_value' => $this->randomString()],
    ]);
    $this->badKey->save();
  }

  /**
   *
   */
  public function testBugherdForm() {
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form = $form_builder->getForm(HsBugherdForm::class);
    $this->assertCount(7, Element::children($form));
    $this->assertArrayHasKey('api_key', $form);
    $this->assertArrayHasKey('project_id', $form);
    $this->assertArrayHasKey('jira_project', $form);
    $this->assertArrayHasKey('status_map', $form);

    $form_state = new FormState();
    $form_state->setValues([
      'api_key' => $this->key->id(),
      'project_id' => NULL,
      'jira_project' => 'TEST',
      'status_map' => [
        HsBugherd::BUGHERDAPI_BACKLOG => '121',
        HsBugherd::BUGHERDAPI_TODO => '122',
        HsBugherd::BUGHERDAPI_DOING => '123',
        HsBugherd::BUGHERDAPI_DONE => '124',
        HsBugherd::BUGHERDAPI_CLOSED => '125',
      ],
    ]);

    $form_builder->submitForm(HsBugherdForm::class, $form_state);
    $this->assertEmpty($form_state->getErrors());

    /** @var \Drupal\hs_bugherd\Form\HsBugherdForm $form_object */
    $form_object = HsBugherdForm::create($this->container);
    $return = $form_object->updateProjectOptions($form, $form_state);
    $this->assertNotFalse(array_search('Archaeology', $return['#options']));

    $form_state->setValue('api_key', $this->badKey->id());
    $form_builder->submitForm(HsBugherdForm::class, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
  }

}
