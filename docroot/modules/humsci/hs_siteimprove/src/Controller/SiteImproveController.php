<?php

namespace Drupal\hs_siteimprove\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hs_siteimprove\SiteImprove;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for SiteImprove details.
 */
class SiteImproveController extends ControllerBase {

  /**
   * The SiteImprove service.
   *
   * @var \Drupal\hs_siteimprove\SiteImprove
   */
  protected $siteImprove;

  /**
   * SiteImproveController constructor.
   *
   * @param \Drupal\hs_siteimprove\SiteImprove $site_improve
   *   The SiteImprove service.
   */
  public function __construct(SiteImprove $site_improve) {
    $this->siteImprove = $site_improve;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hs_siteimprove.connector')
    );
  }

  /**
   * Display site details.
   *
   * @return array
   *   A render array.
   */
  public function siteDetails() {
    $site = $this->siteImprove->getCurrentSite();

    if (!$site) {
      return [
        '#markup' => $this->t('No site found'),
      ];
    }

    $site_array = json_decode(json_encode($site), TRUE);

    $rows = [];
    foreach ($site_array as $key => $value) {
      $rows[] = [
        $key,
        is_array($value) ? print_r($value, TRUE) : $value,
      ];
    }

    return [
      'site_details' => [
        '#type' => 'table',
        '#header' => ['Key', 'Value'],
        '#rows' => $rows,
        '#empty' => $this->t('No data available.'),
      ],
    ];
  }

}
