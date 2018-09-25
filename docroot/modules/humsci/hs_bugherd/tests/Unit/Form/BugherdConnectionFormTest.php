<?php

namespace Drupal\Tests\hs_bugherd\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\Language;
use Drupal\hs_bugherd\Entity\BugherdConnection;
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


    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getListCacheTags')
      ->willReturn([$this->entityTypeId . '_list']);

    $this->entityTypeManager = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('en')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    $this->cacheTagsInvalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidator');

    $container = new ContainerBuilder();
    // Ensure that Entity doesn't use the deprecated entity.manager service.
    $container->set('entity.manager', NULL);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);

    $bugherd_api = $this->createMock(HsBugherd::class);
    $bugherd_api->method('getProjects')->willReturn(['123' => 'Test Project']);
    $bugherd_api->method('getProject')
      ->with('123')
      ->willReturn(['devurl' => 'http://example.com']);
    $container->set('hs_bugherd', $bugherd_api);


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
