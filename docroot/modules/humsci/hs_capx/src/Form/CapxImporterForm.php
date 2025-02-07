<?php

namespace Drupal\hs_capx\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\hs_capx\Entity\CapxImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CapxImporterForm.
 */
class CapxImporterForm extends EntityForm {

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The original entity before saving.
   *
   * @var \Drupal\hs_capx\Entity\CapxImporterInterface
   */
  protected $originalEntity;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    $importer = $this->entity;
    $this->originalEntity = clone $importer;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $importer->label(),
      '#description' => $this->t("Label for the Capx importer."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $importer->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hs_capx\Entity\CapxImporter::load',
      ],
      '#disabled' => !$importer->isNew(),
    ];

    $form['organizations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizations'),
      '#default_value' => $importer->getOrganizations(TRUE),
      '#autocomplete_route_name' => 'hs_capx.org_autocomplete',
    ];

    $form['childOrganizations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include child organizations'),
      '#description' => $this->t('Enable it to retrieve all the members from child organizations.'),
      '#default_value' => $importer->includeChildrenOrgs(),
    ];

    $workgroup_link = Link::fromTextAndUrl($this->t('workgroup manager website')
      ->render(), Url::fromUri('https://workgroup.stanford.edu/'));
    $form['workgroups'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workgroup'),
      '#description' => $this->t('Enter the name(s) of the workgroup(s) you wish to import. Enter multiple organizations by separating them with a comma ",".<br>
        You can learn more about workgroups at Stanford, and get propernames for import, at the @workgroup.', ['@workgroup' => $workgroup_link->toString()]),
      '#default_value' => $importer->getWorkgroups(TRUE),
    ];

    $form['importWhat'] = [
      '#type' => 'radios',
      '#title' => $this->t('What should be imported?'),
      '#options' => [
        CapxImporterInterface::IMPORT_PROFILES => $this->t('Profiles'),
        CapxImporterInterface::IMPORT_PUBLICATIONS => $this->t('Publications'),
        CapxImporterInterface::IMPORT_BOTH => $this->t('Profiles and Publications'),
      ],
      '#default_value' => $importer->importWhat(),
    ];

    $this->buildTaggingForm($form, $form_state);
    return $form;
  }

  /**
   * Build the portion of the importer form for field tagging input.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildTaggingForm(array &$form, FormStateInterface $form_state) {
    $form['tagging'] = [
      '#type' => 'details',
      '#title' => $this->t('Tagging'),
      '#description' => $this->t('Optionally, tag the content imported from CAP with the following terms on the given fields.'),
      '#tree' => TRUE,
      '#open' => !empty($this->entity->getFieldTags()),
    ];
    $fields = $this->entityFieldManager->getFieldDefinitions('node', 'hs_person');
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field) {
      $field_storage = $field->getFieldStorageDefinition();
      if ($field->getType() != 'entity_reference' || $field_storage->getProvider() != 'field' || $field_storage->getSetting('target_type') != 'taxonomy_term') {
        continue;
      }
      $handler_settings = $field->getSetting('handler_settings');

      $terms = [];

      foreach ($handler_settings['target_bundles'] as $term_bundle) {
        foreach ($taxonomy_storage->loadTree($term_bundle) as $term) {
          $terms[$term->tid] = $term->name;
        }
      }
      $form['tagging'][$field->getName()] = [
        '#type' => 'select',
        '#title' => $field->getLabel(),
        '#options' => $terms,
        '#multiple' => $field_storage->getCardinality() == -1,
        '#default_value' => $this->entity->getFieldTags($field->getName()),
        '#empty_option' => $this->t('- None -'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->setValue('organizations', explode(',', $form_state->getValue('organizations')));
    $form_state->setValue('workgroups', explode(',', $form_state->getValue('workgroups')));

    $tagging = array_filter($form_state->getValue('tagging'));
    foreach ($tagging as &$values) {
      if (is_string($values)) {
        $values = [$values];
      }
      $values = array_values($values);
    }
    $form_state->setValue('tagging', $tagging);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Add permission to execute importer.
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load('site_manager');
    if ($role) {
      $role->grantPermission('import hs_capx migration');
      $role->save();
    }

    $importer = $this->entity;
    $status = $importer->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Capx importer.', [
          '%label' => $importer->label(),
        ]));
        break;

      default:
        $this->invalidateMigrationHashes();
        $this->messenger()->addMessage($this->t('Saved the %label Capx importer.', [
          '%label' => $importer->label(),
        ]));
    }
    $form_state->setRedirectUrl($importer->toUrl('collection'));

    Cache::invalidateTags(['migration_plugins', 'hs_capx_config']);
  }

  /**
   * Invalidate migration mapper hashes to be updated on next import.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function invalidateMigrationHashes() {
    // If the importer hasn't been executed, the table will not be created.
    if (!$this->database->schema()->tableExists('migrate_map_hs_capx')) {
      return;
    }

    $entity_query = $this->entityTypeManager->getStorage('node')
      ->getQuery('OR')
      ->accessCheck(FALSE);

    // Find all node ids that are tagged with the fields. This allows us to only
    // invalidate the hashes that are applicable.
    foreach ($this->originalEntity->getFieldTags() as $field_name => $term_ids) {
      foreach ($term_ids as $term_id) {
        $entity_query->condition($field_name, $term_id);
      }
    }

    $entity_ids = array_keys($entity_query->execute());
    if ($entity_ids) {
      $this->database->update('migrate_map_hs_capx')
        ->condition('destid1', $entity_query->execute(), 'IN')
        ->fields(['hash' => ''])
        ->execute();
    }
  }

}
