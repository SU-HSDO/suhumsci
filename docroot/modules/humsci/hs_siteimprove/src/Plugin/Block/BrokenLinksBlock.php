<?php

namespace Drupal\hs_siteimprove\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\hs_siteimprove\SiteImprove;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block displaying SiteImprove details.
 *
 * @Block(
 *   id = "hs_siteimprove_broken_links",
 *   admin_label = @Translation("SiteImprove Broken Links"),
 *   category = @Translation("H&S Blocks")
 * )
 */
class BrokenLinksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The SiteImprove service.
   *
   * @var \Drupal\hs_siteimprove\SiteImprove
   */
  protected $siteImprove;

  /**
   * Constructs a new SiteImproveDetailsBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\hs_siteimprove\SiteImprove $site_improve
   *   The SiteImprove service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SiteImprove $site_improve) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->siteImprove = $site_improve;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hs_siteimprove.connector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // 5 minutes in seconds
    return 300;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $pages = $this->siteImprove->getPagesWithBrokenLinks();
    if (!$pages) {
      return [
        '#markup' => $this->t('No broken links found'),
      ];
    }

    $build = [
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Page'),
          $this->t('Broken Links'),
          $this->t('Actions'),
        ],
        '#rows' => $this->getRows($pages),
        '#empty' => $this->t('No broken links found.'),
        '#attributes' => [
          'class' => ['siteimprove-broken-links-table'],
        ],
      ],
      'full_report' => [
        '#type' => 'link',
        '#title' => $this->t('View Full Broken Links Report'),
        '#url' => $this->getBrokenLinksReportUrl(),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'target' => '_blank',
        ],
        '#prefix' => '<div class="more-link">',
        '#suffix' => '</div>',
      ],
    ];

    return $build;
  }

  /**
   * Get the rows for the table.
   *
   * @param array $pages
   *   The siteimprove pages to get rows for.
   *
   * @return array
   *   The rows for the table.
   */
  private function getRows($pages) {

    // Sort the pages by the number of broken links, in descending order.
    usort($pages, function ($a, $b) {
      return $b->broken_links - $a->broken_links;
    });

    $rows = [];
    $viewed_pages = [];
    foreach ($pages as $page) {
      if (!is_object($page)) {
        continue;
      }

      $title = $page->title ?? $this->t('No title');
      $url = $page->url ?? '';

      // Skip if we've already seen this URL and title combination.
      $page_key = $url . '|' . $title;
      if (isset($viewed_pages[$page_key])) {
        continue;
      }
      $viewed_pages[$page_key] = TRUE;

      $actions = ['data' => ['#markup' => $this->t('No report available')]];
      if (!empty($page->_siteimprove->page_report->href)) {
        $actions = [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View Report'),
            '#url' => Url::fromUri($page->_siteimprove->page_report->href),
            '#attributes' => ['target' => '_blank'],
          ],
        ];
      }

      $rows[] = [
        [
          'data' => [
            '#type' => $url ? 'link' : 'markup',
            '#title' => $title,
            '#url' => $url ? Url::fromUri($url) : NULL,
            '#markup' => $url ? NULL : $title,
            '#attributes' => $url ? ['target' => '_blank'] : [],
          ],
        ],
        [
          'data' => [
            '#markup' => $page->broken_links ?? 0,
          ],
        ],
        $actions,
      ];

      // Limit the number of rows to 20.
      if (count($rows) >= 20) {
        break;
      }
    }

    return $rows;
  }

  /**
   * Get the URL for the broken links report.
   *
   * @return \Drupal\Core\Url
   *   The URL for the broken links report.
   */
  protected function getBrokenLinksReportUrl(): Url {
    $site_id = $this->siteImprove->getCurrentSiteId();
    return Url::fromUri("https://my2.siteimprove.com/QualityAssurance/{$site_id}/Links/Pages/1/UnsuccessfulClicks/Desc?pageSize=100");
  }

}
