<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hs_bugherd\HsBugherd;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdEntityForm.
 */
class BugherdConnectionForm extends EntityForm {

  /**
   * Bugherd API service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hs_bugherd')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(HsBugherd $bugherd_api) {
    $this->bugherdApi = $bugherd_api;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\hs_bugherd\Entity\BugherdEntity $bugherd */
    $bugherd = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bugherd->label(),
      '#description' => $this->t("Label for the Bugherd Connection."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bugherd->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hs_bugherd\Entity\BugherdEntity::load',
      ],
      '#disabled' => !$bugherd->isNew(),
    ];

    $form['bugherdProject'] = [
      '#type' => 'select',
      '#title' => $this->t('Bugherd Project'),
      '#default_value' => $bugherd->getBugherdProject(),
      '#options' => $this->bugherdApi->getProjects(),
    ];

    $form['jiraProject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#description' => $this->t('Which Jira project to connect with'),
      '#default_value' => $bugherd->getJiraProject(),
      '#required' => TRUE,
    ];

    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Website urls'),
      '#required' => TRUE,
      '#default_value' => implode("\n", $bugherd->getUrls()),
    ];

    $form['statusMap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bugherd to Jira Mapping'),
      '#tree' => TRUE,
    ];

    $statusMap = $bugherd->getStatusMap();
    $form['statusMap'][HsBugherd::BUGHERDAPI_BACKLOG] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backlog Status'),
      '#description' => $this->t('The JIRA status code for a "Backlog" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $statusMap[HsBugherd::BUGHERDAPI_BACKLOG] ?? '',
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_TODO] = [
      '#type' => 'textfield',
      '#title' => $this->t('ToDo Status'),
      '#description' => $this->t('The JIRA status code for a "To Do" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $statusMap[HsBugherd::BUGHERDAPI_TODO] ?? '',
    ];

    $form['statusMap'][HsBugherd::BUGHERDAPI_DOING] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doing Status'),
      '#description' => $this->t('The JIRA status code for a "Doing" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $statusMap[HsBugherd::BUGHERDAPI_DOING] ?? '',
    ];

    $form['statusMap'][HsBugherd::BUGHERDAPI_DONE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Done Status'),
      '#description' => $this->t('The JIRA status code for a "Done" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $statusMap[HsBugherd::BUGHERDAPI_DONE] ?? '',
    ];

    $form['statusMap'][HsBugherd::BUGHERDAPI_CLOSED] = [
      '#type' => 'textfield',
      '#title' => $this->t('Closed Status'),
      '#description' => $this->t('The JIRA status code for a "Closed" status. This is normally after the user has accepted the change. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $statusMap[HsBugherd::BUGHERDAPI_CLOSED] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->setValue('urls', explode("\n", $form_state->getValue('urls')));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bugherd = $this->entity;
    $status = $bugherd->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label Bugherd Connection.', [
            '%label' => $bugherd->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label Bugherd Connection.', [
            '%label' => $bugherd->label(),
          ]));
    }
    $form_state->setRedirectUrl($bugherd->toUrl('collection'));
  }

}
