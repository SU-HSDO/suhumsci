<?php

namespace Drupal\react_paragraphs\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get list of available entities.
 *
 * @RestResource(
 *   id = "react_paragraphs_views_display_list",
 *   label = @Translation("Views Display List"),
 *   uri_paths = {
 *     "canonical" = "/entity-list/view-displays"
 *   }
 * )
 */
class ReactParagraphsViewDisplayListResource extends ResourceBase {

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

    $ignored_views = [
      'archive',
      'block_content',
      'content',
      'content_recent',
      'files',
      'frontpage',
      'glossary',
      'media',
      'hs_search',
      'hs_manage_content',
      'media_entity_browser',
      'redirect',
      'redirect_404',
      'taxonomy_term',
      'user_admin_people',
      'who_s_new',
      'who_s_online',
      'watchdog',
    ];
    $data = [];
    $storage = $this->entityTypeManager->getStorage('view');

    foreach ($storage->loadMultiple() as $view) {
      if (in_array($view->id(), $ignored_views)) {
        continue;
      }

      $displays = $view->get('display');
      $data['views'][] = [
        'value' => $view->id(),
        'label' => $view->label(),
      ];

      foreach ($displays as $display_id => $display) {
        if ($display['display_plugin'] != 'block') {
          continue;
        }

        $data['display'][$view->id()][] = [
          'value' => $display_id,
          'label' => $display['display_title'],
        ];
      }
    }
    return new JsonResponse($data);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [];
  }

}
