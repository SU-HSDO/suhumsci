<?php

namespace Drupal\letsencrypt_challenge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
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

    // Automated challenges get uploaded to a directory. Here we are grabbing
    // that file from the directory and using it as the contents.
    if ($directory = Settings::get('letsencrypt_challenge_directory', '')) {
      $directory = rtrim($directory, '/ ');
      if ($key && file_exists("$directory/.well-known/acme-challenge/$key")) {
        $response->setContent(file_get_contents("$directory/.well-known/acme-challenge/$key"));
      }
    }
    return $response;
  }

}
