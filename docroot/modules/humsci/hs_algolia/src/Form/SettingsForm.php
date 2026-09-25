<?php

namespace Drupal\hs_algolia\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hs_algolia\Overrides\ConfigOverrides;
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

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Algolia search'),
      '#description' => $this->t('Index published content into Algolia. Disabling removes every record from the Algolia index.'),
      '#default_value' => $index?->status() ?? FALSE,
    ];

    $form['application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#description' => $this->t('From the API Keys page of the Algolia application.'),
      '#default_value' => $config->get('application_id'),
      '#maxlength' => 128,
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
      '#description' => $this->t('Name of the index inside the Algolia application. It is created on first use. Use the site name, for example <code>archaeology</code>.'),
      '#default_value' => $config->get('index_name'),
      '#maxlength' => 128,
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
    $this->config(ConfigOverrides::SETTINGS)
      ->set('application_id', trim((string) $form_state->getValue('application_id')))
      ->set('api_key', (string) $form_state->getValue('api_key'))
      ->set('index_name', trim((string) $form_state->getValue('index_name')))
      ->save();

    // The overrides for the server and index read the settings just saved, so
    // drop anything loaded earlier in this request.
    $this->configFactory->reset(ConfigOverrides::SERVER);
    $this->configFactory->reset(ConfigOverrides::INDEX);
    $server_storage = $this->entityTypeManager->getStorage('search_api_server');
    $index_storage = $this->entityTypeManager->getStorage('search_api_index');
    $server_storage->resetCache([self::SERVER_ID]);
    $index_storage->resetCache([self::INDEX_ID]);

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

    if ($form_state->getValue('enabled')) {
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
    else {
      // Disabling the index asks the Algolia backend to clear the remote
      // records. Search API saves the entity before that runs and only catches
      // its own exceptions, so an unreachable Algolia or a missing key leaves
      // the index disabled with its records still public. Report that rather
      // than failing the request.
      $clear_error = NULL;

      if ($index->status()) {
        try {
          $index->disable()->save();
        }
        catch (\Throwable $e) {
          $clear_error = $e->getMessage();
          $this->getLogger('hs_algolia')->error('Algolia records were not removed while disabling the index: @message', ['@message' => $clear_error]);
        }
      }

      if ($server->status()) {
        try {
          $server->disable()->save();
        }
        catch (\Throwable $e) {
          $this->getLogger('hs_algolia')->error('Unable to disable the Algolia server: @message', ['@message' => $e->getMessage()]);
        }
      }

      if ($clear_error === NULL) {
        $this->messenger()->addStatus($this->t('Algolia search is disabled.'));
      }
      else {
        $this->messenger()->addError($this->t('Algolia search is disabled, but its records could not be removed and are still public. Re-enable Algolia, correct the credentials, then disable it again, or delete the index in the Algolia dashboard. The error was: @message', [
          '@message' => $clear_error,
        ]));
      }
    }

    parent::submitForm($form, $form_state);
  }

}
