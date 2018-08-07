<?php

namespace Drupal\letsencrypt_challenge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChallengeForm.
 */
class ChallengeForm extends FormBase {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ChallengeForm form.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'challenge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['challenge'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Challenge string'),
      '#description' => $this->t('Challenge provided by Lets Encrypt. (This is saved as state, not config.)'),
      '#maxlength' => 512,
      '#size' => 128,
      '#default_value' => $this->state->get('letsencrypt_challenge.challenge', ''),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->state->set('letsencrypt_challenge.challenge', $form_state->getValue('challenge'));

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
