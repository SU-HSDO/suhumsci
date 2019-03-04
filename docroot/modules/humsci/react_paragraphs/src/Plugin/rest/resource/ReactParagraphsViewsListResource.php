<?php

namespace Drupal\react_paragraphs\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get list of available views and its displays.
 *
 * @RestResource(
 *   id = "react_paragraphs_views_list",
 *   label = @Translation("Views"),
 *   uri_paths = {
 *     "canonical" = "/entity/views"
 *   }
 * )
 */
class ReactParagraphsViewsListResource extends ResourceBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $views = [];
    $view_storage = $this->entityTypeManager->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    foreach ($view_storage->loadMultiple() as $view) {
      $views[$view->id()] = ['label' => $view->label(), 'displays' => []];
      foreach ($view->get('display') as $display) {
        if ($display['id'] == 'default') {
          continue;
        }
        $views[$view->id()]['displays'][$display['id']] = $display['display_title'];
      }
    }
    return new JsonResponse($views);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [];
  }

}
