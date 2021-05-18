<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation to make the link title a term selection.
 *
 * @FieldWidget(
 *   id = "link_term_title",
 *   label = @Translation("Link Term Title"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkTermTitleWidget extends LinkWidget {

  /**
   * Core entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['title_vid'] = NULL;
    return $settings;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $options = [];
    $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->loadMultiple();
    foreach ($vocabs as $vocab) {
      $options[$vocab->id()] = $vocab->label();
    }

    $elements['title_vid'] = [
      '#title' => $this->t('Taxonomy Vocabulary'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('title_vid'),
    ];
    return $elements;
  }

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if (empty($this->getSetting('title_vid'))) {
      return $element;
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $this->getSetting('title_vid')]);
    foreach ($terms as &$term) {
      $term = $term->label();
    }

    // Change the title from a text field into a term selection.
    $title = $element['title'];
    $element['title'] = [
      '#type' => 'select',
      '#options' => $terms,
      '#empty_option' => '- None -',
    ];

    $element['title'] += $title;
    return $element;
  }

}
