<?php

namespace Drupal\hs_page_reports\EventSubscriber;

use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to record kernel exceptions.
 *
 * @package Drupal\hs_page_reports\EventSubscriber
 */
class PageReportsEventSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $requestStack;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new Fast404EventSubscriber instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Request Stack.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   Database connection service.
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

  /**
   * Event listener to record kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   Thrown event.
   *
   * @throws \Exception
   */
  public function onKernelException(ExceptionEvent $event) {
    if (!method_exists($event->getThrowable(), 'getStatusCode')) {
      return;
    }

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
        'code' => $event->getThrowable()->getStatusCode(),
      ];
    }

    $record['count']++;
    $this->database->merge('hs_page_reports')
      ->key('path', $record['path'])
      ->fields($record)
      ->execute();

  }

}
