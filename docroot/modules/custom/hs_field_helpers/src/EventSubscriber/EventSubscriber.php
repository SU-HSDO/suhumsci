<?php

namespace Drupal\hs_field_helpers\EventSubscriber;

use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\menu_block\Plugin\Block\MenuBlock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber
 *
 * @package Drupal\hs_field_helpers\EventSubscriber
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Active trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $activeTrail;

  /**
   * Menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $linkManager;

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $active_trail
   *   Active trail service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $link_manager
   *   Menu link manager service.
   */
  public function __construct(MenuActiveTrailInterface $active_trail, MenuLinkManagerInterface $link_manager) {
    $this->activeTrail = $active_trail;
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY][] = [
      'onLayoutBuilderRender',
      1,
    ];
    return $events;
  }

  /**
   * Set the label of the block for layout builder blocks.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Layout builder render array event.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function onLayoutBuilderRender(SectionComponentBuildRenderArrayEvent $event) {
    if ($event->getPlugin() instanceof MenuBlock) {
      $build = $event->getBuild();
      // If there are no menu items in the menu block, set the build to an
      // empty array so that the block label doens't get rendered.
      if (empty($build['content']['#items'])) {
        $event->setBuild([]);
        return;
      }

      $menu_name = $event->getPlugin()->getDerivativeId();
      $this->setMenuBlockLabel($build, $menu_name);
      $event->setBuild($build);
    }
  }

  /**
   * Change the block label into a link to that root item.
   *
   * @param array $build
   *   The render array build.
   * @param string $menu_name
   *   The machine name of the menu for the menu block.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function setMenuBlockLabel(array &$build, $menu_name) {
    $label = &$build['#configuration']['label'];

    $trail = $this->activeTrail->getActiveTrailIds($menu_name);
    $trail = array_filter($trail);

    if (empty($trail) || empty($label)) {
      return;
    }

    // The last item of the trail is the root menu item.
    $root = end($trail);

    // Create the link to put as the label.
    if ($root_link = $this->linkManager->createInstance($root)) {
      $link = Link::fromTextAndUrl($label, $root_link->getUrlObject());
      $label = $link->toRenderable();

      if (count($trail) == 1) {
        $label['#attributes']['class'][] = 'is-active';
      }
    }
  }

}
