<?php

namespace Drupal\hs_bugherd\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\hs_bugherd\HsBugherd;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdConnectionForm.
 *
 * @package Drupal\hs_bugherd\Form
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If connection to Bugherd fails, tell the user.
    if (!$this->bugherdApi->isConnectionSuccessful()) {
      $link = Link::createFromRoute('API settings', 'hs_bugherd.bugherd_connection_settings_form')
        ->toString();
      return ['#markup' => '<h2>' . $this->t('No connection to Bugherd. Please configure the @link.', ['@link' => $link]) . '</h2>'];
    }

    // If no bugherd projects are available, tell the user and don't allow them
    // to enter any additional information.
    if (empty($this->getAvailableBugherdProjects())) {
      return ['#markup' => '<h2>' . $this->t('No available bugherd projects') . '</h2>'];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\hs_bugherd\Entity\BugherdConnection $bugherd */
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
      '#machine_name' => ['exists' => '\Drupal\hs_bugherd\Entity\BugherdConnection::load'],
      '#disabled' => !$bugherd->isNew(),
    ];

    $form['bugherdProject'] = [
      '#type' => 'select',
      '#title' => $this->t('Bugherd Project'),
      '#default_value' => $bugherd->getBugherdProject(),
      '#options' => $this->getAvailableBugherdProjects(),
      '#ajax' => [
        'callback' => '::updateProjectUrls',
        'wrapper' => 'project-urls',
      ],
    ];

    $form['jiraProject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jira Project'),
      '#description' => $this->t('Which Jira project to connect with'),
      '#default_value' => $bugherd->getJiraProject(),
      '#required' => TRUE,
    ];

    $url = '';
    if ($project_id = $bugherd->getBugherdProject()) {
      $project = $this->bugherdApi->getProject($project_id);
      $url = $project['devurl'];
    }

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Website Url'),
      '#description' => $this->t('More urls may be configured for this project.'),
      '#default_value' => $url,
      '#size' => strlen($url),
      '#attributes' => ['disabled' => TRUE],
      '#prefix' => '<div id="project-urls">',
      '#suffix' => '</div>',
    ];

    $form['statusMap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bugherd to Jira Mapping'),
      '#tree' => TRUE,
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_BACKLOG] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backlog Status'),
      '#description' => $this->t('The JIRA status code for a "Backlog" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $bugherd->getStatusMap()[HsBugherd::BUGHERDAPI_BACKLOG] ?? '',
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_TODO] = [
      '#type' => 'textfield',
      '#title' => $this->t('ToDo Status'),
      '#description' => $this->t('The JIRA status code for a "To Do" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $bugherd->getStatusMap()[HsBugherd::BUGHERDAPI_TODO] ?? '',
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_DOING] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doing Status'),
      '#description' => $this->t('The JIRA status code for a "Doing" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $bugherd->getStatusMap()[HsBugherd::BUGHERDAPI_DOING] ?? '',
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_DONE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Done Status'),
      '#description' => $this->t('The JIRA status code for a "Done" status. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $bugherd->getStatusMap()[HsBugherd::BUGHERDAPI_DONE] ?? '',
    ];
    $form['statusMap'][HsBugherd::BUGHERDAPI_CLOSED] = [
      '#type' => 'textfield',
      '#title' => $this->t('Closed Status'),
      '#description' => $this->t('The JIRA status code for a "Closed" status. This is normally after the user has accepted the change. Use comma separated numbers for multiple states.'),
      '#required' => TRUE,
      '#default_value' => $bugherd->getStatusMap()[HsBugherd::BUGHERDAPI_CLOSED] ?? '',
    ];
    return $form;
  }

  /**
   * Get the Bugherd projects which haven't been mapped to a Jira project yet.
   *
   * @return array
   *   Associative array of Bugherd projects.
   */
  protected function getAvailableBugherdProjects() {
    $projects = $this->bugherdApi->getProjects();
    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach (BugherdConnection::loadMultiple() as $connection) {
      if ($connection->id() == $this->entity->id()) {
        continue;
      }
      unset($projects[$connection->getBugherdProject()]);
    }
    return $projects;
  }

  /**
   * Ajax callback to display the url of the chosen Bugherd project.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Url with new default value.
   */
  public function updateProjectUrls(array $form, FormStateInterface $form_state) {
    $project_id = $form_state->getValue('bugherdProject');
    $project = $this->bugherdApi->getProject($project_id);
    $form['url']['#default_value'] = $project['project']['devurl'];
    $form['url']['#size'] = strlen($project['project']['devurl']);
    return $form['url'];
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
