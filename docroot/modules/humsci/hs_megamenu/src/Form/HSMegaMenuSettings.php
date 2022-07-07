<?php

namespace Drupal\hs_megamenu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HSMegaMenuSettings extends ConfigFormBase {

  public function getFormId() {
    return 'hs_megamenu_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('hs_megamenu.settings');

    $form['use_hs_megamenu'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use Stanford HumSci Mega Menu'),
      '#description' => $this->t('Will replace the original main menu with newer mega menu'),
      '#default_value' => $config->get('hs_megamenu.use_hs_megamenu'),
    );

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hs_megamenu.settings');
    
    $config->set('hs_megamenu.use_hs_megamenu', $form_state->getValue('use_hs_megamenu'));
    
    $config->save();
    \Drupal::service('cache.render')->invalidateAll();
    return parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return [
      'hs_megamenu.settings',
    ];
  }

}