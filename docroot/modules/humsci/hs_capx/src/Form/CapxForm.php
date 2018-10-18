<?php

namespace Drupal\hs_capx\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class CapxForm.
 */
class CapxForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'capx_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_capx.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('hs_capx.settings');

    $form['organizations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizations'),
      '#default_value' => $config->get('organizations'),
      '#autocomplete_route_name' => 'hs_capx.org_autocomplete',
    ];

    $form['child_organizations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include child organizations'),
      '#description' => $this->t('Enable it to retrieve all the members from child organizations.'),
      '#default_value' => $config->get('child_organizations'),
    ];

    $workgroup_link = Link::fromTextAndUrl($this->t('workgroup manager website')
      ->render(), Url::fromUri('https://workgroup.stanford.edu/'));
    $form['workgroups'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workgroup'),
      '#description' => $this->t('Enter the name(s) of the workgroup(s) you wish to import. Enter multiple organizations by separating them with a comma ",".<br>
        You can learn more about workgroups at Stanford, and get propernames for import, at the @workgroup.', ['@workgroup' => $workgroup_link->toString()]),
      '#default_value' => $config->get('workgroups'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable('hs_capx.settings')
      ->set('organizations', $form_state->getValue('organizations'))
      ->set('child_organizations', $form_state->getValue('child_organizations'))
      ->set('workgroups', $form_state->getValue('workgroups'))
      ->save();

    Cache::invalidateTags(['migration_plugins']);
  }

}
