<?php

namespace Drupal\su_humsci_profile\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Class HumsciCommands.
 *
 * @package Drupal\su_humsci_profile\Commands
 */
class HumsciCommands extends DrushCommands {

  /**
   * Core entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HumsciCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Core entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Convert row paragraphs to collections.
   *
   * @command humsci:convert-row-to-collection
   *
   * @param string $node_type
   *   Node bundle id.
   * @param string $field_name
   *   Paragraph field name.
   * @param string $collection_type
   *   New collection paragraph type id.
   */
  public function rowsToCollections($node_type, $field_name, $collection_type) {
    \Drupal::moduleHandler()->loadInclude('su_humsci_profile', 'post_update.php');
    _su_humsci_profile_enable_paragraph('node', $node_type, $field_name, $collection_type);

    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => $node_type]);

    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {

      $changed = FALSE;
      $new_component_values = [];
      $field_values = $node->get($field_name)->getValue();
      foreach ($field_values as $component) {
        /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
        $paragraph = $paragraph_storage->load($component['target_id']);

        if ($paragraph->bundle() != 'hs_row') {
          $new_component_values[] = $component;
          continue;
        }
        $changed = TRUE;
        $style = $paragraph->get('field_paragraph_style')->getString();
        $row_components = $paragraph->get('field_hs_row_components')
          ->getValue();
        /** @var \Drupal\paragraphs\ParagraphInterface $collection */
        $collection = $paragraph_storage->create([
          'type' => $collection_type,
          'field_hs_collection_per_row' => count($row_components),
          'field_paragraph_style' => $style,
        ]);
        $collection->save();
        $collection_components = [];

        foreach ($row_components as $row_component) {
          /** @var \Drupal\paragraphs\ParagraphInterface $component */
          $component = $paragraph_storage->load($row_component['target_id']);
          $new_component = $component->createDuplicate();
          $new_component->setParentEntity($collection, 'field_hs_collection_items');
          $new_component->save();
          $collection_components[] = [
            'target_id' => $new_component->id(),
            'target_revision_id' => $new_component->getRevisionId(),
          ];
        }
        $collection->set('field_hs_collection_items', $collection_components)
          ->save();

        $new_component_values[] = [
          'target_id' => $collection->id(),
          'target_revision_id' => $collection->getRevisionId(),
        ];
      }
      if ($changed) {
        $node->setNewRevision();
        $node->set($field_name, $new_component_values)
          ->save();
      }
    }

    _su_humsci_profile_disable_paragraph('node', $node_type, $field_name, 'hs_row');
  }

  /**
   * Convert row paragraphs to collections.
   *
   * @command humsci:rows-to-collection
   */
  public function convertRowsToCollection() {
    $this->rowsToCollections('hs_basic_page', 'field_hs_page_components', 'hs_collection');
  }

}
