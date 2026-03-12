<?php

namespace Drupal\hs_admin\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects taxonomy term view pages to the admin content listing.
 */
class TaxonomyTermViewRedirectSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Messenger service.
   */
  protected MessengerInterface $messenger;

  /**
   * Current user.
   */
  protected AccountInterface $currentUser;

  public function __construct(MessengerInterface $messenger, AccountInterface $current_user) {
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run relatively early but after routing is available.
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 30];
    return $events;
  }

  /**
   * Handles redirects when viewing taxonomy terms.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();

    if ($request->getMethod() !== 'GET') {
      return;
    }

    $route_name = (string) $request->attributes->get('_route');
    if ($route_name !== 'entity.taxonomy_term.canonical') {
      return;
    }

    $term = $request->attributes->get('taxonomy_term');
    if (!$term instanceof TermInterface) {
      return;
    }

    // Currently, we don't use _any_ full-page taxonomy displays.  If one day
    // that changes, we can exclude those vocabs here.
    //
    $url = Url::fromUri('internal:/admin/content', [
      'query' => [
        'term-name' => $term->label(),
      ],
    ])->toString();

    // Prepare the info message and, if the user has access, an edit link.
    if ($this->currentUser->isAuthenticated()) {
      $parts = [];
      $parts[] = (string) $this->t("You've been redirected to a list of all content with this taxonomy term.");
      if ($term->access('update', $this->currentUser)) {
        $edit_url = Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $term->id()])
          ->toString();
        $parts[] = (string) $this->t('Alternatively, <a href=":url">edit the taxonomy term</a>.', [
          ':url' => $edit_url,
        ]);
      }
      $final_message = Markup::create(implode(' ', $parts));
      $this->messenger->addMessage($final_message, MessengerInterface::TYPE_STATUS);
    }

    $response = new RedirectResponse($url, 302);
    $event->setResponse($response);
  }

}
