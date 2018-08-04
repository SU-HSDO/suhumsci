<?php

namespace Drupal\letsencrypt_challenge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ChallengeController.
 */
class ChallengeController extends ControllerBase {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ChallengeController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * Content.
   *
   * @return string
   *   Return challenge string.
   */
  public function content($key = NULL) {
    $response = new Response();
    $response->setMaxAge(0);
    if ($challenge = $this->state->get('letsencrypt_challenge.challenge', '')) {
      $response->setContent($challenge);
    }
    if ($key) {
      $wellknown_directory = \Drupal::service('file_system')
        ->realpath('public://.well-known/acme-challenge');
      if (file_exists("$wellknown_directory/$key")) {
        $response->setContent(file_get_contents("$wellknown_directory/$key"));
      }
    }

    return $response;
  }

}
