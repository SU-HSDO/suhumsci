<?php

namespace Drupal\mrc_ds_blocks\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for block routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Create block routes for every entity.
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {

      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();

        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = array(
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          );
        }

        $options['parameters']['mrc_ds_blocks_id'] = array(
          'type' => 'mrc_ds_block',
          'entity_type' => $entity_type->getBundleEntityType(),
        );

        $defaults_delete = [
          'entity_type_id' => $entity_type_id,
          '_form' => '\Drupal\mrc_ds_blocks\Form\MrcDsBlocksDeleteForm',
        ];

        $defaults_add = [
          'entity_type_id' => $entity_type_id,
          '_form' => '\Drupal\mrc_ds_blocks\Form\MrcDsBlocksAddForm',
          '_title' => 'Add Block',
        ];

        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults_add['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
          $defaults_delete['bundle'] = $defaults_add['bundle'];
        }

        // Routes to delete block.
        $route = new Route(
          "$path/display/{mrc_ds_blocks_id}/delete-block",
          ['context' => 'view'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_delete_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/{mrc_ds_blocks_id}/delete-block",
          ['context' => 'view'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_delete_$entity_type_id.display.view_mode", $route);

        // Routes to add blocks.
        $route = new Route(
          "$path/display/add-block",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_add_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/add-block",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_add_$entity_type_id.display.view_mode", $route);

        $route = new Route(
          "$path/display/add-block/{block_id}",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_add_$entity_type_id.display.block", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/add-block/{block_id}",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("mrc_ds_blocks.mrc_ds_blocks_add_$entity_type_id.display.view_mode.block", $route);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after field_ui, config_translation.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -210);
    return $events;
  }

}
