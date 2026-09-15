<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\ServerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that Algolia credential fields are hidden on the server form.
 */
#[Group('hs_algolia')]
class ServerFormAlterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../hs_algolia.module';

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Build the backend_config portion of a server form as contrib renders it.
   */
  protected function buildForm(): array {
    return [
      'backend_config' => [
        'help' => ['#markup' => '<p>Find your keys at algolia.com</p>'],
        'application_id' => ['#type' => 'textfield', '#required' => TRUE],
        'api_key' => ['#type' => 'textfield', '#required' => TRUE],
        'disable_truncate' => ['#type' => 'checkbox'],
      ],
    ];
  }

  /**
   * Build a form state whose form object edits a server with the given backend.
   */
  protected function mockFormState(?string $backend_id): FormStateInterface {
    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackendId')->willReturn($backend_id);

    $form_object = $this->createMock(EntityFormInterface::class);
    $form_object->method('getEntity')->willReturn($server);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getFormObject')->willReturn($form_object);
    return $form_state;
  }

  /**
   * Credential fields are hidden and the help text explains where they live.
   */
  public function testAlgoliaCredentialsAreHidden(): void {
    $form = $this->buildForm();
    hs_algolia_form_search_api_server_form_alter($form, $this->mockFormState('search_api_algolia'));

    $this->assertFalse($form['backend_config']['application_id']['#access']);
    $this->assertFalse($form['backend_config']['api_key']['#access']);
    $this->assertStringContainsString('secrets.settings.php', (string) $form['backend_config']['help']['#markup']);
    $this->assertArrayNotHasKey('#access', $form['backend_config']['disable_truncate']);
  }

  /**
   * Servers on other backends are left alone.
   */
  public function testOtherBackendsAreUntouched(): void {
    $form = $this->buildForm();
    $expected = $form;
    hs_algolia_form_search_api_server_form_alter($form, $this->mockFormState('search_api_db'));

    $this->assertSame($expected, $form);
  }

  /**
   * A server with no backend chosen yet is left alone.
   */
  public function testMissingBackendIsUntouched(): void {
    $form = $this->buildForm();
    $expected = $form;
    hs_algolia_form_search_api_server_form_alter($form, $this->mockFormState(NULL));

    $this->assertSame($expected, $form);
  }

  /**
   * A form object that is not an entity form is left alone.
   */
  public function testNonEntityFormIsUntouched(): void {
    $form = $this->buildForm();
    $expected = $form;

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getFormObject')->willReturn($this->createMock(FormInterface::class));
    hs_algolia_form_search_api_server_form_alter($form, $form_state);

    $this->assertSame($expected, $form);
  }

}
