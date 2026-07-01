<?php

namespace Drupal\hs_siteimprove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hs_siteimprove\SiteImprove;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form displaying SiteImprove site details with a Reset button.
 */
class SiteImproveDetailsForm extends FormBase {

  /**
   * The SiteImprove service.
   *
   * @var \Drupal\hs_siteimprove\SiteImprove
   */
  protected $siteImprove;

  /**
   * SiteImproveDetailsForm constructor.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_siteimprove_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site = $this->siteImprove->getCurrentSite();

    if (!$site) {
      $form['no_site'] = [
        '#markup' => $this->t('No site found'),
      ];
    }
    else {
      $site_array = json_decode(json_encode($site), TRUE);
      $rows = [];
      foreach ($site_array as $key => $value) {
        $rows[] = [
          $key,
          is_array($value) ? print_r($value, TRUE) : $value,
        ];
      }

      $form['site_details'] = [
        '#type' => 'table',
        '#header' => ['Key', 'Value'],
        '#rows' => $rows,
        '#empty' => $this->t('No data available.'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Recalculate Site ID'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_id = $this->siteImprove->getCurrentSiteId(TRUE);

    if ($site_id) {
      $this->messenger()->addStatus($this->t('Site ID has been reset to @id.', ['@id' => $site_id]));
    }
    else {
      $settings_url = Url::fromRoute('hs_siteimprove.api_settings_form')->toString();
      $this->messenger()->addError($this->t('Could not retrieve site ID from SiteImprove. Please check the <a href=":url">API Settings</a>.', [':url' => $settings_url]));
    }
  }

}
