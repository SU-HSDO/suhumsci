<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener related to redirect entities.
 */
class RedirectEvents implements EventSubscriberInterface {

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_PRE_SAVE => 'entityPresave',
    ];
  }

  /**
   * Event listener constructor.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   Path alias manager service.
   */
  public function __construct(AliasManagerInterface $path_alias_manager) {
    $this->aliasManager = $path_alias_manager;
  }

  /**
   * Before a redirect is saved, perform some work.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   Triggered event.
   */
  public function entityPresave(EntityPresaveEvent $event) {
    if ($event->getEntity()->getEntityTypeId() != 'redirect') {
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $redirect */
    $redirect = $event->getEntity();
    $destination = $redirect->get('redirect_redirect')->getString();

    // If a redirect is added to go to the aliased path of a node (often from
    // importing redirect), change the destination to target the node instead.
    // This works if the destination is `/about` or `/node/9`.
    if (preg_match('/^internal:(\/.*)/', $destination, $matches)) {
      // Find the internal path from the alias.
      $path = $this->aliasManager->getPathByAlias($matches[1]);

      // Grab the node id from the internal path and use as the destination.
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $redirect->set('redirect_redirect', 'entity:node/' . $matches[1]);
      }
    }

    // Purge everything for the source url so that it can redirect without any
    // intervention.
    self::purgePath($redirect->get('redirect_source')->getString());
  }

  /**
   * Purges a relative path using the generated absolute url.
   *
   * @param string $path
   *   Drupal site relative path.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\TypeUnsupportedException
   */
  protected static function purgePath($path) {
    // If this module exists, we know the purge services exist too.
    $purge_exists = \Drupal::moduleHandler()
      ->moduleExists('purge_processor_lateruntime');

    if (!$purge_exists) {
      Cache::invalidateTags(['4xx-response']);
      return;
    }

    $url = Url::fromUserInput('/' . trim($path, '/'), ['absolute' => TRUE])
      ->toString();

    $purgeInvalidationFactory = \Drupal::service('purge.invalidation.factory');
    $purgeProcessors = \Drupal::service('purge.processors');
    $purgePurgers = \Drupal::service('purge.purgers');

    $processor = $purgeProcessors->get('lateruntime');
    $invalidations = [$purgeInvalidationFactory->get('url', $url)];

    try {
      $purgePurgers->invalidate($processor, $invalidations);
    }
    catch (\Exception $e) {
      \Drupal::logger('humsci_events_listeners')->error($e->getMessage());
    }
  }

}
