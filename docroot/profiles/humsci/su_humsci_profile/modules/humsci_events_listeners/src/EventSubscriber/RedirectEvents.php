<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Installer\InstallerKernel;
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
    if (
      $event->getEntity()->getEntityTypeId() != 'redirect' ||
      InstallerKernel::installationAttempted()
    ) {
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
    $url = self::fixPurgeUrl($url);

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

  /**
   * When running something via CLI, the domain might not be correct, fix it.
   *
   * @param string $url
   *   Url string about to be purged.
   *
   * @return string
   *   Corrected url.
   */
  protected static function fixPurgeUrl(string $url): string {
    // When the url is updated while in the UI, the url will have a correct
    // domain.
    if (PHP_SAPI != 'cli') {
      return $url;
    }

    // Make sure the url is https
    $url = preg_replace('/^http:/', 'https:', $url);

    // Get the domain so we can fix it up.
    $domain = str_replace(parse_url($url, PHP_URL_PATH), '', $url);

    // Use the domain set in the domain redirect for simplicity.
    $canonical_domain = \Drupal::config('domain_301_redirect.settings')
      ->get('domain');

    if ($canonical_domain) {
      return str_replace($domain, $canonical_domain, $url);
    }
    // If the domain redirect isn't configured, just fix it up as much as we
    // can to avoid any errors.
    return str_replace($domain, str_replace('_', '-', str_replace('__', '.', $domain)), $url);
  }

}
