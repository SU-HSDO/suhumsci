<?php

namespace Drupal\hs_page_reports\EventSubscriber;

use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PageReportsEventSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $requestStack;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new Fast404EventSubscriber instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Request Stack.
   */
  public function __construct(RequestStack $request_stack, Connection $db_connection) {
    $this->requestStack = $request_stack;
    $this->database = $db_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::EXCEPTION][] = ['onKernelException', 0];
    return $events;
  }

  public function onKernelException(GetResponseForExceptionEvent $event) {
    $path = $this->requestStack->getCurrentRequest()->getPathInfo();

    $record = $this->database->select('hs_page_reports', 'h')
      ->fields('h')
      ->condition('path', $path)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      $record = [
        'path' => $path,
        'count' => 0,
        'code' => $event->getException()->getStatusCode(),
      ];
    }

    $record['count']++;
    $this->database->merge('hs_page_reports')
      ->key('path', $record['path'])
      ->fields($record)
      ->execute();

  }

}
