<?php

namespace Drupal\letsencrypt_challenge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
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
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ChallengeController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   */
  public function __construct(StateInterface $state, FileSystemInterface $file_system) {
    $this->state = $state;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('file_system')
    );
  }

  /**
   * Return the lets encrypt challenge string..
   *
   * @param string $key
   *   Name of the challenge file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return challenge string.
   */
  public function content($key = NULL) {
    $response = new Response();
    $response->setMaxAge(0);
    if ($challenge = $this->state->get('letsencrypt_challenge.challenge', '')) {
      $response->setContent($challenge);
    }

    $public_directory = $this->fileSystem->realpath('public://');

    // Automated challenges get uploaded to a directory. Here we are grabbing
    // that file from the directory and using it as the contents.
    if ($directory = Settings::get('letsencrypt_challenge_directory', $public_directory)) {
      $directory = rtrim($directory, '/ ');
      if ($key && file_exists("$directory/.well-known/acme-challenge/$key")) {
        $response->setContent(file_get_contents("$directory/.well-known/acme-challenge/$key"));
      }
    }
    return $response;
  }

}
