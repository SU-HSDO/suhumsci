<?php

namespace Drupal\su_humsci_profile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for updating content access settings.
 *
 * The content access module allows different permissions per content type. The
 * configuration (content_access.settings) is config ignored. This service will
 * update relevant access setting per bundle or per node without affecting any
 * existing settings.
 */
class HumsciContentAccessUpdater {
  use StringTranslationTrait;

  /**
   * The required roles and their allowed operations.
   *
   * @var array
   */
  protected $requiredRoles = [
    'intranet_viewer' => ['view', 'view_own'],
    'administrator' => ['view', 'view_own', 'update', 'update_own', 'delete', 'delete_own'],
    'site_manager' => ['view', 'view_own', 'update', 'update_own', 'delete', 'delete_own'],
  ];

  /**
   * Updates bundle-level content access settings.
   *
   * @param string $bundle
   *   The bundle to update settings for.
   *
   * @return array
   *   Array of results messages.
   */
  public function updateBundleSettings(string $bundle): array {
    $results = [];
    $settings = content_access_get_settings('all', $bundle);

    foreach ($this->requiredRoles as $role => $operations) {
      foreach ($operations as $op) {
        if (isset($settings[$op]) && in_array($role, $settings[$op])) {
          continue;
        }
        $settings[$op][] = $role;
        $results[] = t('Added %role to %op permissions.', ['%role' => $role, '%op' => $op]);
      }
    }

    if (empty($settings['per_node'])) {
      $settings['per_node'] = TRUE;
      $results[] = t('Enforced per-node Content Access.');
    }

    if ($results) {
      content_access_set_settings($settings, $bundle);
      $results[] = t('Saved Content Access settings for %bundle.', ['%bundle' => $bundle]);
      node_access_rebuild(TRUE);
      $results[] = t('Rebuilt node access.');
    }

    return $results;
  }

  /**
   * Updates settings for a single node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to update.
   *
   * @return array
   *   Array of results messages.
   */
  public function updateNodeSettings(NodeInterface $node): array {
    $results = [];
    $node_settings = content_access_get_per_node_settings($node);
    $changed = FALSE;

    if ($node_settings) {
      foreach ($this->requiredRoles as $role => $operations) {
        foreach ($operations as $op) {
          if (isset($node_settings[$op]) && in_array($role, $node_settings[$op])) {
            continue;
          }
          $node_settings[$op][] = $role;
          $results[] = t('Added %role to %op permissions.', ['%role' => $role, '%op' => $op]);
          $changed = TRUE;
        }
      }

      if ($changed) {
        content_access_save_per_node_settings($node, $node_settings);
      }
    }

    return $results;
  }

}
