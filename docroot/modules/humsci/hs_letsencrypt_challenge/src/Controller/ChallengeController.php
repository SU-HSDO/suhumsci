<?php

namespace Drupal\letsencrypt_challenge\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  public function content() {
    $response = new CacheableResponse('', 200);

    $response->setContent($this->state->get('letsencrypt_challenge.challenge', ''));

    return $response;
  }

}
