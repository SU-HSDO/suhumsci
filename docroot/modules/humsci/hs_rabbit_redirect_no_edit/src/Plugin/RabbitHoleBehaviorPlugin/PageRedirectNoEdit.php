<?php

namespace Drupal\hs_rabbit_redirect_no_edit\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects to another page, except for users with edit access.
 *
 * Behaves identically to the core "page_redirect" behavior but skips the
 * redirect for users who have update access to the entity. Those users see
 * the normal canonical page along with a status message informing them that
 * anonymous users will be redirected.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "page_redirect_no_edit",
 *   label = @Translation("Page redirect (skip for editors)")
 * )
 */
class PageRedirectNoEdit extends PageRedirect {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RabbitHoleEntityPluginManager $rhepm, ModuleHandlerInterface $mhi, Token $token, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rhepm, $mhi, $token);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rabbit_hole_entity_plugin'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(
    array &$form,
    FormStateInterface $form_state,
    $form_id,
    ?EntityInterface $entity = NULL,
    $entity_is_bundle = FALSE,
    ?ImmutableConfig $bundle_settings = NULL,
  ) {
    parent::settingsForm($form, $form_state, $form_id, $entity, $entity_is_bundle, $bundle_settings);
    $form['rabbit_hole']['redirect']['info'] = [
      '#markup' => '<p>' . $this->t('If the user can edit this entity, then no redirect will happen (the page will be displayed). Users that cannot edit will be redirected.') . '</p>',
      '#weight' => -10,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\rabbit_hole\Exception\InvalidRedirectResponseException
   */
  public function performAction(EntityInterface $entity, ?Response $current_response = NULL) {
    $target = $this->getActionTarget($entity);

    // If the user has edit access and there is a valid redirect target, skip
    // the redirect. The status message is shown via
    // hs_rabbit_redirect_no_edit_entity_view() so that it also appears for
    // users with the "rabbit hole bypass" permission (for whom this plugin is
    // never invoked).
    if (!empty($target) && $entity->access('update', $this->currentUser)) {
      return NULL;
    }

    // The redirect decision depends on the current user's permissions, so the
    // resulting redirect response must vary per permission set.
    $this->cacheMetadata->addCacheContexts(['user.permissions']);

    return parent::performAction($entity, $current_response);
  }

}
