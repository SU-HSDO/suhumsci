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
      if (file_exists("/mnt/gfs/swshumscidev/files/$key")) {
        $response->setContent(file_get_contents("/mnt/gfs/swshumsci.dev/files/$key"));
      }
    }

    return $response;
  }

}
