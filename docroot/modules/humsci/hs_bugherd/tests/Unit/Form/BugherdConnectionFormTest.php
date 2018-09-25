<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\hs_bugherd\Form\BugherdConnectionForm;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\Tests\UnitTestCase;

/**
 * Class BugherdConnectionFormTest
 *
 * @covers \Drupal\hs_bugherd\Form\BugherdConnectionForm
 * @group hs_bugherd
 */
class BugherdConnectionFormTest extends UnitTestCase {

  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();

    $bugherd_api = $this->createMock(HsBugherd::class);
    $bugherd_api->method('getProjects')->willReturn(['123' => 'Test Project']);
    $bugherd_api->method('getProject')
      ->with('123')
      ->willReturn(['devurl' => 'http://example.com']);
    $container->set('hs_bugherd', $bugherd_api);
    $entity_type = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $container->set('entity_type.repository', $entity_type);
    $entity_type_manager = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->willReturn($this->getMockForAbstractClass(ConfigEntityStorageInterface::class));
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Test Entity form.
   */
  public function testBugherdConnectionForm() {
    $form_object = BugherdConnectionForm::create(\Drupal::getContainer());
    $form = [];
    $form_state = new FormState();
    $form_object->buildForm($form, $form_state);
  }

}
