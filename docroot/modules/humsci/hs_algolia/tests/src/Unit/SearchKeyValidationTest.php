<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\hs_algolia\Form\SettingsForm;
use Drupal\key\KeyInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that only a search-only key can be sent to visitors' browsers.
 */
#[Group('hs_algolia')]
class SearchKeyValidationTest extends UnitTestCase {

  /**
   * Validate a search key against a form whose Algolia lookup is faked.
   *
   * @param string $search_key
   *   The search key entered on the form.
   * @param array|null $acl
   *   Permissions Algolia reports for the key, or NULL when it rejects it.
   *
   * @return string|null
   *   The error on the search key field, or NULL when it is valid.
   */
  protected function validate(string $search_key, ?array $acl): ?string {
    $write_key = $this->createMock(KeyInterface::class);
    $write_key->method('getKeyValue')->willReturn('write-key');
    $key_storage = $this->createMock(EntityStorageInterface::class);
    $key_storage->method('load')->with('algolia_write_key')->willReturn($write_key);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('key')->willReturn($key_storage);

    $form_object = new class(
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(TypedConfigManagerInterface::class),
      $entity_type_manager,
      $this->createMock(Connection::class),
    ) extends SettingsForm {

      /**
       * Permissions the fake Algolia lookup returns.
       */
      public ?array $acl = NULL;

      /**
       * {@inheritdoc}
       */
      protected function getKeyAcl(string $application_id, string $key): ?array {
        return $this->acl;
      }

      /**
       * Expose the search key validation.
       */
      public function validateSearch(array $form, $form_state): void {
        $this->validateSearchKey($form, $form_state);
      }

    };
    $form_object->acl = $acl;
    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form_state = (new FormState())->setValues([
      'application_id' => 'APP',
      'api_key' => 'algolia_write_key',
      'search_api_key' => $search_key,
      'search_enabled' => TRUE,
    ]);
    $form = ['search' => ['search_api_key' => ['#title' => 'Search-only API key']]];
    $form_object->validateSearch($form, $form_state);

    $error = $form_state->getErrors()['search_api_key'] ?? NULL;
    return $error === NULL ? NULL : (string) $error;
  }

  /**
   * Keys that can only read are accepted.
   */
  public function testSearchOnlyKeyIsAccepted(): void {
    $this->assertNull($this->validate('search-key', ['search']));
    // The Search API Key Algolia creates in every application.
    $this->assertNull($this->validate('search-key', ['browse', 'listIndexes', 'search', 'settings']));
  }

  /**
   * Keys that are missing, unknown or too powerful are refused.
   */
  public function testUnsafeKeysAreRefused(): void {
    $this->assertStringContainsString('required', $this->validate('', ['search']));
    $this->assertStringContainsString('write API key', $this->validate('write-key', ['search']));
    $this->assertStringContainsString('did not accept', $this->validate('search-key', NULL));
    $this->assertStringContainsString('cannot search', $this->validate('search-key', ['browse']));
    $error = $this->validate('search-key', ['addObject', 'browse', 'logs', 'search']);
    $this->assertStringContainsString('not safe to show to visitors (addObject, logs)', $error);
  }

}
