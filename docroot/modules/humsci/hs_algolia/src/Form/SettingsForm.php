<?php

namespace Drupal\hs_algolia\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hs_algolia\Overrides\ConfigOverrides;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ServerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Per-site Algolia settings.
 *
 * @see \Drupal\hs_algolia\Overrides\ConfigOverrides
 */
class SettingsForm extends ConfigFormBase {

  const SERVER_ID = 'hs_algolia';

  const INDEX_ID = 'hs_algolia';

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_algolia_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [ConfigOverrides::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(ConfigOverrides::SETTINGS);
    $index = $this->entityTypeManager->getStorage('search_api_index')->load(self::INDEX_ID);
    $enabled = $index?->status() ?? FALSE;

    // Which Algolia index the site writes to cannot change while Algolia is
    // on. Only lock the fields once they hold something worth protecting, so a
    // half-configured site can still be corrected.
    $locked = $enabled && $config->get('application_id') && $config->get('index_name');
    $locked_note = $this->t('Disable Algolia search to change this. Changing it while enabled would leave the records in the old Algolia index public.');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Algolia search'),
      '#description' => $this->t('Index published content into Algolia. Disabling removes every record from the Algolia index.'),
      '#default_value' => $enabled,
    ];

    $form['application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#description' => $locked
        ? $locked_note
        : $this->t('From the API Keys page of the Algolia application.'),
      '#default_value' => $config->get('application_id'),
      '#maxlength' => 128,
      '#disabled' => $locked,
    ];

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Write API key'),
      '#description' => $this->t('A key entity holding an Algolia API key with write access to the index. Use a key scoped to this index rather than the Admin API key.'),
      '#key_filters' => ['type' => 'authentication'],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['index_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Algolia index name'),
      '#description' => $locked
        ? $locked_note
        : $this->t('Name of the index inside the Algolia application. It is created on first use. Use the site name, for example <code>archaeology</code>.'),
      '#default_value' => $config->get('index_name'),
      '#maxlength' => 128,
      '#disabled' => $locked,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!$form_state->getValue('enabled')) {
      return;
    }

    foreach (['application_id', 'api_key', 'index_name'] as $field) {
      if (trim((string) $form_state->getValue($field)) === '') {
        $form_state->setErrorByName($field, $this->t('@field is required to enable Algolia search.', [
          '@field' => $form[$field]['#title'],
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $server_storage = $this->entityTypeManager->getStorage('search_api_server');
    $index_storage = $this->entityTypeManager->getStorage('search_api_index');

    // Load without overrides so saving does not write the runtime credentials
    // into the entities. Search API refuses to enable an index whose server is
    // disabled, so the server changes first on enable and last on disable.
    /** @var \Drupal\search_api\ServerInterface|null $server */
    $server = $server_storage->loadOverrideFree(self::SERVER_ID);
    /** @var \Drupal\search_api\IndexInterface|null $index */
    $index = $index_storage->loadOverrideFree(self::INDEX_ID);
    if (!$server || !$index) {
      $this->messenger()->addError($this->t('The Algolia server or index is missing. Import configuration and try again.'));
      return;
    }

    $enabling = (bool) $form_state->getValue('enabled');

    // Turn things off before the new settings replace the old ones, or the
    // clear targets whichever Algolia index was just typed in rather than the
    // live one.
    if (!$enabling) {
      $this->applyDisabled($server, $index);
    }

    $this->config(ConfigOverrides::SETTINGS)
      ->set('application_id', trim((string) $form_state->getValue('application_id')))
      ->set('api_key', (string) $form_state->getValue('api_key'))
      ->set('index_name', trim((string) $form_state->getValue('index_name')))
      ->save();

    // The overrides for the server and index read the settings just saved, so
    // drop anything loaded earlier in this request.
    $this->configFactory->reset(ConfigOverrides::SERVER);
    $this->configFactory->reset(ConfigOverrides::INDEX);
    $server_storage->resetCache([self::SERVER_ID]);
    $index_storage->resetCache([self::INDEX_ID]);

    if ($enabling) {
      /** @var \Drupal\search_api\ServerInterface $server */
      $server = $server_storage->loadOverrideFree(self::SERVER_ID);
      /** @var \Drupal\search_api\IndexInterface $index */
      $index = $index_storage->loadOverrideFree(self::INDEX_ID);
      try {
        if (!$server->status()) {
          $server->enable()->save();
          $server_storage->resetCache([self::SERVER_ID]);
        }
        if (!$index->status()) {
          $index->enable()->save();
        }
      }
      catch (\Throwable $e) {
        $this->getLogger('hs_algolia')->error('Unable to enable Algolia search: @message', ['@message' => $e->getMessage()]);
        $this->messenger()->addError($this->t('The settings were saved, but Algolia search could not be enabled: @message', [
          '@message' => $e->getMessage(),
        ]));
        return;
      }

      $this->messenger()->addStatus($this->t('Algolia search is enabled. Content is indexed on cron. To index everything now, use <a href=":url">Index now</a> on the index page.', [
        ':url' => Url::fromRoute('entity.search_api_index.canonical', ['search_api_index' => self::INDEX_ID])->toString(),
      ]));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Turn Algolia off, clearing the remote index.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The Algolia server, loaded override free.
   * @param \Drupal\search_api\IndexInterface $index
   *   The Algolia index, loaded override free.
   */
  protected function applyDisabled(ServerInterface $server, IndexInterface $index): void {
    $clear_error = NULL;
    $cleared = FALSE;

    if ($index->status()) {
      try {
        $index->disable()->save();
        $cleared = TRUE;
      }
      catch (\Throwable $e) {
        $clear_error = $e->getMessage();
        $this->getLogger('hs_algolia')->error('Algolia records were not removed while disabling the index: @message', ['@message' => $clear_error]);
      }
    }

    // Turn the server off even when the clear failed, so nothing else writes.
    if ($server->status()) {
      try {
        $server->disable()->save();
      }
      catch (\Throwable $e) {
        $this->getLogger('hs_algolia')->error('Unable to disable the Algolia server: @message', ['@message' => $e->getMessage()]);
      }
    }

    // A successful clear emptied the whole Algolia index, so anything queued
    // for deletion from it is already gone. Leave the queue alone when the
    // clear failed: those records are still in Algolia and still need removing.
    if ($cleared) {
      $this->discardQueuedDeletions();
    }

    if ($clear_error === NULL) {
      $this->messenger()->addStatus($this->t('Algolia search is disabled.'));
      return;
    }

    $this->messenger()->addError($this->t('Algolia search is disabled, but its records could not be removed and are still public. Re-enable Algolia, correct the credentials, then disable it again, or delete the index in the Algolia dashboard. The error was: @message', [
      '@message' => $clear_error,
    ]));
  }

  /**
   * Drop deletions queued against this site's Algolia index.
   *
   * Runs before the new settings are saved, so the configured index name is
   * still the one that was just cleared. Other indexes keep their queue.
   *
   * @see hs_algolia_cron()
   */
  protected function discardQueuedDeletions(): void {
    $index_name = (string) $this->config(ConfigOverrides::SETTINGS)->get('index_name');
    if ($index_name === '') {
      return;
    }

    $this->database->delete('search_api_algolia_deleted_items')
      ->condition('index_id', $index_name)
      ->execute();
  }

}
