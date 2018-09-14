<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptService;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\key\Entity\Key;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HsBugherdForm.
 *
 * @package Drupal\hs_bugherd\Form
 */
class HsBugherdForm extends ConfigFormBase {

  /**
   * Bugherd API service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * Encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_bugherd';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bugherdapi.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('hs_bugherd'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, HsBugherd $bugherd_api, EncryptService $encrypt) {
    parent::__construct($config_factory);
    $this->requestStack = $request_stack;
    $this->bugherdApi = $bugherd_api;
    $this->encryption = $encrypt;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('bugherdapi.settings');

    $keys = [];
    /** @var \Drupal\key\Entity\Key $key */
    foreach (Key::loadMultiple() as $key) {
      $keys[$key->id()] = $key->label();
    }

    $form['api_key'] = [
      '#type' => 'select',
      '#title' => $this->t('BugHerd API key'),
      '#default_value' => $config->get('api_key'),
      '#options' => $keys,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateProjectOptions',
        'wrapper' => 'project-id',
        'effect' => 'fade',
      ],
    ];

    $projects = [];
    if ($this->bugherdApi->isConnectionSuccessful()) {
      $projects = $this->bugherdApi->getProjects();
    }

    $form['project_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Bugherd Project'),
      '#default_value' => $config->get('project_id'),
      '#options' => $projects,
      '#prefix' => '<div id="project-id">',
      '#suffix' => '</div>',
    ];
    $form['jira_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#default_value' => $config->get('jira_project'),
      '#required' => TRUE,
    ];

    $form['status_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bugherd to Jira Mapping'),
      '#tree' => TRUE,
    ];

    $form['status_map'][HsBugherd::BUGHERDAPI_BACKLOG] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backlog Status'),
      '#description' => $this->t('The JIRA status code for a "Backlog" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $config->get('status_map.' . HsBugherd::BUGHERDAPI_BACKLOG),
    ];

    $form['status_map'][HsBugherd::BUGHERDAPI_TODO] = [
      '#type' => 'textfield',
      '#title' => $this->t('ToDo Status'),
      '#description' => $this->t('The JIRA status code for a "To Do" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $config->get('status_map.' . HsBugherd::BUGHERDAPI_TODO),
    ];
    $form['status_map'][HsBugherd::BUGHERDAPI_DOING] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doing Status'),
      '#description' => $this->t('The JIRA status code for a "Doing" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $config->get('status_map.' . HsBugherd::BUGHERDAPI_DOING),
    ];

    $form['status_map'][HsBugherd::BUGHERDAPI_DONE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Done Status'),
      '#description' => $this->t('The JIRA status code for a "Done" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $config->get('status_map.' . HsBugherd::BUGHERDAPI_DONE),
    ];

    $form['status_map'][HsBugherd::BUGHERDAPI_CLOSED] = [
      '#type' => 'textfield',
      '#title' => $this->t('Closed Status'),
      '#description' => $this->t('The JIRA status code for a "Closed" status. This is normally after the user has accepted the change. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $config->get('status_map.' . HsBugherd::BUGHERDAPI_CLOSED),
    ];

    return $form;
  }

  /**
   * Ajax handler to update project options.
   *
   * @param array $form
   *   Complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Modified select form element.
   */
  public function updateProjectOptions(array $form, FormStateInterface $form_state) {
    $project_options = [];

    $key = Key::load($form_state->getValue('api_key'));
    $this->bugherdApi->setApiKey($key->getKeyValue());
    if ($this->bugherdApi->isConnectionSuccessful()) {
      $project_options = $this->bugherdApi->getProjects();
    }
    $form['project_id']['#options'] = $project_options;
    return $form['project_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $key = Key::load($form_state->getValue('api_key'));
    $this->bugherdApi->setApiKey($key->getKeyValue());
    $test = $this->bugherdApi->getOrganization();
    if (isset($test['error'])) {
      $form_state->setError($form['api_key'], $test['error']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\key\Entity\Key $key */
    $key = Key::load($form_state->getValue('api_key'));
    $config_dependencies = [$key->getConfigDependencyName()];

    $this->config('bugherdapi.settings')
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('api_key', $key->id())
      ->set('jira_project', $form_state->getValue('jira_project'))
      ->set('status_map', $form_state->getValue('status_map'))
      ->set('dependencies.config', $config_dependencies)
      ->save();
  }

}
