<?php

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'HsLoginBlock' block.
 *
 * @Block(
 *  id = "hs_login_block",
 *  admin_label = @Translation("HS Login Block"),
 * )
 */
class HsLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Only show the block to logged out users.
    if ($account->id()) {
      $access = AccessResult::forbidden();
      return $return_as_object ? $access : FALSE;
    }
    return parent::access($account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'link_text' => $this->t('Login'),
      'preface' => [
        'value' => '',
        'format' => 'minimal_html',
      ],
    ];
    return $config + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['preface'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Preface Text'),
      '#format' => $this->configuration['preface']['format'],
      '#default_value' => $this->configuration['preface']['value'],
      '#allowed_formats' => ['minimal_html'],
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $this->configuration['link_text'],
      '#required' => TRUE,
    ];
    $form['#attached']['library'][] = 'hs_blocks/login.admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['preface'] = $form_state->getValue('preface');
    $this->configuration['link_text'] = $form_state->getValue('link_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $destination = $this->requestStack->getCurrentRequest()->getPathInfo();
    $route = 'user.login';
    // If simple saml is enabled, we want to use that route instead of the core
    // login form.
    if ($this->moduleHandler->moduleExists('simplesamlphp_auth')) {
      $route = 'simplesamlphp_auth.saml_login';
    }

    $build = [
      '#theme' => 'hs_blocks_login',
      '#link' => [
        '#type' => 'link',
        '#title' => $this->configuration['link_text'],
        '#url' => Url::fromRoute($route, [], ['query' => ['destination' => trim($destination, '/ ')]]),
        '#attributes' => ['class' => ['decanter-button']],
      ],
      '#context' => ['entity:user', 'entity:node'],
    ];
    if ($this->configuration['preface']['value']) {
      $build['#preface'] = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['preface']['value'],
        '#format' => $this->configuration['preface']['format'],
      ];
    }
    return $build;
  }

}
