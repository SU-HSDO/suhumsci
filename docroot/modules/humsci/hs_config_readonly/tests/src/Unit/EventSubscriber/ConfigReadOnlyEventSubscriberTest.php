<?php

namespace Drupal\Tests\hs_config_readonly\Unit\EventSubscriber;

use Drupal\config_filter\Config\FilteredStorageInterface;
use Drupal\config_filter\Plugin\ConfigFilterPluginManager;
use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * Class ConfigReadOnlyEventSubscriberTest
 *
 * @group hs_config_readonly
 * @coversDefaultClass \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
 */
class ConfigReadOnlyEventSubscriberTest extends UnitTestCase {

  /**
   * @var \Drupal\hs_config_readonly\EventSubscriber\ConfigReadOnlyEventSubscriber
   */
  protected $eventSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('invokeAll')->willReturn(['*', 'ignore.wildcard.config.*']);

    $config = $this->createMock(Config::class);
    $config->method('get')->willReturnCallback([$this, 'configGetCallback']);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->willReturn($config);

    $config_storage = $this->createMock(FilteredStorageInterface::class);
    $config_storage->method('listAll')->willReturn(['locked.config.test']);

    $ignore_filter = $this->createMock(IgnoreFilter::class);
    $ignore_filter->method('filterListAll')->willReturn([
      'ignore.whole.config',
      'ignore.wildcard.config.*',
      'ignore.part.config:test',
    ]);

    $filter_manager = $this->createMock(ConfigFilterPluginManager::class);
    $filter_manager->method('hasDefinition')->willReturn(TRUE);
    $filter_manager->method('createInstance')->willReturn($ignore_filter);

    $wizard_config = $this->createMock(ConfigEntityInterface::class);
    $wizard_config->method('getConfigDependencyName')
      ->willReturn('locked.config.test');

    $entity_storage = $this->createMock(EntityStorageBase::class);
    $entity_storage->method('load')->willReturn($wizard_config);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $event_subscriber = new ConfigReadOnlyEventSubscriber($module_handler, $config_factory, $config_storage, $filter_manager, $entity_type_manager);

    $this->eventSubscriber = $event_subscriber;
  }

  /**
   * Callback method when a mock config is asked for a value.
   *
   * @param string $arg
   *   Config key.
   *
   * @return mixed
   *   Config value.
   */
  public function configGetCallback($arg) {

    switch ($arg) {
      case 'bypass_form_ids':
        return ['bypassed_form'];
        break;
      case 'form_ids':
        return [];
        break;

      case 'excluded_modules':
        return [];
        break;

      case 'ignored_config_entities':
        return [
          'ignore.whole.config',
          'ignore.wildcard.config.*',
          'ignore.part.config:test',
        ];
        break;
    }
  }

  /**
   * Test a simple form is not read only.
   */
  public function testSimpleForm() {
    $events = ConfigReadOnlyEventSubscriber::getSubscribedEvents();

    $this->assertEquals([
      'config_readonly_form_event' => [
        [
          'onFormAlter',
          200,
        ],
      ],
    ], $events);

    $form_state = new FormState();
    $form_state->setBuildInfo(['callback_object' => new TestFormCallbackObject()]);

    $form = [];

    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertFalse($event->isFormReadOnly());

    $form_state->setBuildInfo(['callback_object' => new TestFormCallbackObject('bypassed_form')]);
    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertFalse($event->isFormReadOnly());
  }

  /**
   * Test a config form.
   */
  public function testConfigForm() {
    $form_state = new FormState();
    $form = [];

    $form_state->setBuildInfo(['callback_object' => new TestConfigFormCallbackObject()]);
    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertTRUE($event->isFormReadOnly());

    $form_state->setBuildInfo(['callback_object' => new TestConfigFormCallbackObject('ignore.wildcard.config.testing')]);
    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertFalse($event->isFormReadOnly());

    $form_state->setBuildInfo(['callback_object' => new TestConfigFormCallbackObject('ignore.part.config')]);
    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertFalse($event->isFormReadOnly());
  }

  /**
   * Tests entity config forms.
   */
  public function testEntityForm() {
    $form_state = new FormState();
    $form = [];

    $config_entity = $this->createMock(ConfigEntityInterface::class);
    $config_entity->method('getConfigDependencyName')
      ->willReturn('locked.config.test');

    $form_callback = new TestEntityFormCallbackObject();
    $form_callback->setEntity($config_entity);

    $form_state->setBuildInfo(['callback_object' => $form_callback]);

    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertTRUE($event->isFormReadOnly());
  }

  /**
   * Test an entity wizard form.
   */
  public function testWizardForm() {
    $form_state = new FormState();
    $form = [];

    $form_callback = new TestEntityFormWizardCallbackObject();

    $form_state->setBuildInfo(['callback_object' => $form_callback]);

    $event = new ReadOnlyFormEvent($form_state, $form);
    $this->eventSubscriber->onFormAlter($event);
    $this->assertTRUE($event->isFormReadOnly());
  }

}

class TestFormCallbackObject {

  protected $testValue;

  public function __construct($test_value = NULL) {
    $this->testValue = $test_value;
  }

  public function getFormId() {
    return $this->testValue ?: 'test';
  }

}

class TestConfigFormCallbackObject extends ConfigFormBase {

  protected $configName;

  public function __construct($config_name = NULL) {
    $this->configName = $config_name ?: 'locked.config.test';
  }

  protected function getEditableConfigNames() {
    return [$this->configName];
  }

  public function getFormId() {
    return 'test';
  }
}

class TestEntityFormCallbackObject extends EntityForm {

  public function getFormId() {
    return 'entity_form';
  }

}

class TestEntityFormWizardCallbackObject extends EntityFormWizardBase {

  public function __construct() {
  }

  public function getFormId() {
    return 'wizard_form';
  }

  public function getMachineLabel() {
  }

  public function getWizardLabel() {
  }

  public function getEntityType() {
    return 'test_entity';
  }

  public function getOperations($cached_values) {
  }

  public function exists() {
  }

}
