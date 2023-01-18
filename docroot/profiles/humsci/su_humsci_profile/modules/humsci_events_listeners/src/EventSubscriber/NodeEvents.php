<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\node\NodeInterface;
use Drupal\preprocess_event_dispatcher\Event\NodePreprocessEvent;
use Drupal\rabbit_hole\BehaviorInvokerInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for block entities.
 */
class NodeEvents implements EventSubscriberInterface {

  /**
   * Rabbit hole behavior invoker service.
   *
   * @var \Drupal\rabbit_hole\BehaviorInvokerInterface
   */
  protected $rabbitHoleBehavior;

  /**
   * Rabbit hole behavior plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rabbitHolePluginManager;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_PRE_SAVE => 'entityPresave',
      EntityHookEvents::ENTITY_DELETE => 'entityDelete',
      'preprocess_node' => 'preprocessNode',
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\rabbit_hole\BehaviorInvokerInterface $rabbitHoleBehavior
   *   Rabbit hole behavior invoker service.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager $rabbitHolePluginManager
   *   Rabbit hole behavior plugin manager.
   */
  public function __construct(BehaviorInvokerInterface $rabbitHoleBehavior = NULL, RabbitHoleBehaviorPluginManager $rabbitHolePluginManager = NULL) {
    $this->rabbitHoleBehavior = $rabbitHoleBehavior;
    $this->rabbitHolePluginManager = $rabbitHolePluginManager;
  }

  /**
   * On node save, perform some actions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   Triggered Event.
   */
  public function entityPresave(EntityPresaveEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  /**
   * On node delete, perform some actions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent $event
   *   Triggered Event.
   */
  public function entityDelete(EntityDeleteEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  /**
   * Flush extra caches for a node action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  protected static function flushEntityCaches(EntityInterface $entity) {
    if ($entity instanceof NodeInterface) {
      Cache::invalidateTags(['config:search_api.index.default_index']);
    }
  }

  /**
   * When preprocessing the node page, add the rabbit hole behavior message.
   *
   * @param \Drupal\preprocess_event_dispatcher\Event\NodePreprocessEvent $event
   *   Triggered Event.
   */
  public function preprocessNode(NodePreprocessEvent $event) {
    $node = $event->getVariables()->get('node');
    if (
      $event->getVariables()->get('page') &&
      ($plugin = $this->getRabbitHolePlugin($node))
    ) {
      $redirect_response = $plugin->performAction($node);

      // The action returned from the redirect plugin might be to show the
      // page. If it is, we don't want to display the message.
      if ($redirect_response instanceof TrustedRedirectResponse) {

        $content = $event->getVariables()->getByReference('content');
        $message = [
          '#theme' => 'rabbit_hole_message',
          '#destination' => $redirect_response->getTargetUrl(),
          '#attached' => ['library' => ['humsci_events_listeners/rabbit_hole_message']],
        ];
        $event->getVariables()
          ->set('content', ['rh_message' => $message] + $content);
      }
    }
  }

  /**
   * Get the rabbit hole behavior plugin for the given node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Node with rabbit hole.
   *
   * @return \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface|null
   *   Redirect behavior plugin if applicable.
   */
  protected function getRabbitHolePlugin(NodeInterface $entity): ?RabbitHoleBehaviorPluginInterface {
    if (isset($this->rabbitHoleBehavior)) {
      $values = $this->rabbitHoleBehavior->getRabbitHoleValuesForEntity($entity);
      if (isset($values['rh_action']) && $values['rh_action'] == 'page_redirect') {
        return $this->rabbitHolePluginManager->createInstance($values['rh_action'], $values);
      }
    }
    return NULL;
  }

}
