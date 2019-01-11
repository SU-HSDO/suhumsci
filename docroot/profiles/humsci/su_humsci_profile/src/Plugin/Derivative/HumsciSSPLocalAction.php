<?php

namespace Drupal\su_humsci_profile\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HumsciSSPLocalAction to definite derivative local action links.
 *
 * @package Drupal\su_humsci_profile\Plugin\Derivative
 */
class HumsciSSPLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Module Handler service..
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_definition) {
    $this->derivatives = [];
    if ($this->moduleHandler->moduleExists('stanford_ssp')) {

      // Normally this would be in a yml file, but since its dependent on a
      // module that we dont always want enabled, we'll add the local action
      // link here.
      $this->derivatives['su_humsci_profile.add_user'] = [
        'route_name' => 'stanford_ssp.create_user',
        'title' => $this->t('Add SUNetID User'),
        'appears_on' => ['entity.user.collection'],
      ];
    }

    return $this->derivatives;
  }

}
