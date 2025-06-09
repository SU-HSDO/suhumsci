<?php

namespace Drupal\hs_role_description\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * Configure role_description settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * EntityTypeManager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Array of roles that can have description.
   *
   * @var \Drupal\user\RoleInterface[]
   */
  private array $roles = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hs_role_description_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_role_description.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hs_role_description.settings');

    $role_storage = $this->entityTypeManager->getStorage('user_role');
    $this->roles = $role_storage->loadMultiple();
    unset($this->roles[RoleInterface::ANONYMOUS_ID]);
    unset($this->roles[RoleInterface::AUTHENTICATED_ID]);

    $form['roles'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        $this->t('Role'),
        $this->t('Description'),
      ],
    ];

    foreach ($this->roles as $role) {
      /** @var string $role_id */
      $role_id = $role->id();
      $form['roles'][$role_id] = [];
      $form['roles'][$role_id]['role'] = [
        '#type' => 'item',
        '#title' => $role_id,
      ];
      $form['roles'][$role_id]['description'] = [
        '#type' => 'textarea',
        '#rows' => 2,
        '#default_value' => $config->get('role_description')[$role_id] ?? '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hs_role_description.settings');

    $role_description = [];
    foreach ($this->roles as $role) {
      if ($value = $form_state->getValue(['roles', $role->id(), 'description'])) {
        $role_description[$role->id()] = $value;
      }
    }
    $config->set('role_description', $role_description);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
