<?php

namespace Drupal\su_humsci_profile\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteConfigureForm as CoreSiteConfigureForm;

/**
 * Class SiteConfigureForm.
 *
 * Overrides core configure form and enables the config readonly module after
 * submit.
 *
 * @package Drupal\su_humsci_profile\Form
 */
class SiteConfigureForm extends CoreSiteConfigureForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $email = 'test@test.com';
    $form['site_information']['site_name']['#default_value'] = $this->t('SWS HumSci');
    $form['site_information']['site_mail']['#default_value'] = $email;
    $form['admin_account']['account']['mail']['#default_value'] = $email;
    $form['admin_account']['account']['name']['#default_value'] = 'swsadmin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    try {
      $this->moduleInstaller->install(['config_readonly']);
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Unable to lock configuration'));
    }
  }

}
