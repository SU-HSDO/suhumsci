<?php

namespace Drupal\hs_person\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\externalauth\AuthmapInterface;
use Drupal\node\NodeInterface;

/**
 * Service for handling person node authorship assignment.
 *
 * This service handles the business logic for:
 * - Finding anonymous-authored hs_person nodes with matching SUNet ID.
 * - Assigning ownership of matching nodes to users.
 * - Ensuring users have appropriate roles for content ownership.
 * - Notifying users of assigned content.
 */
class PersonAuthorship {

  use StringTranslationTrait;

  /**
   * Required roles for content ownership.
   */
  private const REQUIRED_ROLES = ['author', 'contributor', 'site_manager'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Constructs a new PersonAuthorship object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    AuthmapInterface $authmap,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->authmap = $authmap;
  }

  /**
   * Process person authorship assignment for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return int
   *   The number of nodes that were assigned to the user.
   */
  public function processPersonAuthorship(AccountInterface $account): int {
    if ($this->shouldSkipProcessing($account)) {
      return 0;
    }

    $authname = $this->authmap->get($account->id(), 'samlauth');
    if (empty($authname)) {
      return 0;
    }

    $matching_nodes = $this->findMatchingPersonNodes($authname, $account->id());
    if (empty($matching_nodes)) {
      return 0;
    }

    $updated_nodes = $this->assignNodesToUser($matching_nodes, $account, $authname);
    if ($updated_nodes > 0) {
      $this->logAssignment($updated_nodes, $account, $authname);
      $this->notifyUser($matching_nodes, $authname);
    }

    return $updated_nodes;
  }

  /**
   * Check if processing should be skipped for this account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if processing should be skipped, FALSE otherwise.
   */
  protected function shouldSkipProcessing(AccountInterface $account): bool {
    $skip_roles = ['anonymous', 'site_manager', 'administrator'];
    return !empty(array_intersect($skip_roles, $account->getRoles()));
  }

  /**
   * Find hs_person nodes with matching SUNet ID.
   *
   * @param string $authname
   *   The user's authname (SUNet ID) to search for.
   * @param int $user_id
   *   The current user's ID to exclude nodes they already own.
   * @param int $limit
   *   Maximum number of nodes to return (default: 10).
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of node entities that match the SUNet ID.
   */
  protected function findMatchingPersonNodes(string $authname, int $user_id = 0, int $limit = 10): array {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_person')
      ->condition('uid', $user_id, '<>')
      ->condition('field_hs_person_sunet_id', $authname);

    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    // Safety limit: only process the first N matching nodes.
    $total_matches = count($result);
    if ($total_matches > $limit) {
      $result = array_slice($result, 0, $limit);
      $this->loggerFactory->get('hs_person')->warning(
        'Found @total matches for user authname "@authname", but limited processing to first @limit nodes. @skipped nodes were not processed.',
        [
          '@total' => $total_matches,
          '@authname' => $authname,
          '@limit' => $limit,
          '@skipped' => $total_matches - $limit,
        ]
      );
    }

    return $node_storage->loadMultiple($result);
  }

  /**
   * Assign nodes to the user and ensure they have required roles.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of node entities to assign.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $authname
   *   The user's authname (SUNet ID) used for matching.
   *
   * @return int
   *   The number of nodes that were successfully assigned.
   */
  protected function assignNodesToUser(array $nodes, AccountInterface $account, string $authname): int {
    $updated_count = 0;

    foreach ($nodes as $node) {
      if ($this->isNodeMatch($node, $authname)) {
        $this->ensureUserHasRequiredRole($account);
        $this->assignNodeToUser($node, $account);
        $updated_count++;
      }
    }

    return $updated_count;
  }

  /**
   * Check if a node's SUNet ID field matches the user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param string $authname
   *   The authname (SUNet ID) to match against.
   *
   * @return bool
   *   TRUE if the SUNet ID matches, FALSE otherwise.
   */
  protected function isNodeMatch(NodeInterface $node, string $authname): bool {
    $sunet_field = $node->get('field_hs_person_sunet_id');
    return !$sunet_field->isEmpty() && $sunet_field->value === $authname;
  }

  /**
   * Assign a single node to a user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to assign.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  protected function assignNodeToUser(NodeInterface $node, AccountInterface $account): void {
    $node->setOwnerId($account->id());
    $node->save();
  }

  /**
   * Log the assignment of nodes to a user.
   *
   * @param int $count
   *   The number of nodes assigned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $authname
   *   The user's authname (SUNet ID) used for matching.
   */
  protected function logAssignment(int $count, AccountInterface $account, string $authname): void {
    $this->loggerFactory->get('hs_person')->info(
      'Updated @count anonymous-authored person node(s) to be owned by user @user_id (SUNet ID: @authname)',
      [
        '@count' => $count,
        '@user_id' => $account->id(),
        '@authname' => $authname,
      ]
    );
  }

  /**
   * Send notification messages to the user about assigned nodes.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of node entities that were assigned.
   * @param string $authname
   *   The user's authname (SUNet ID) used for matching.
   */
  protected function notifyUser(array $nodes, string $authname): void {
    $matching_nodes = array_filter($nodes, function (NodeInterface $node) use ($authname) {
      return $this->isNodeMatch($node, $authname);
    });

    if (empty($matching_nodes)) {
      return;
    }

    $node_count = count($matching_nodes);
    $node_links = array_map(function (NodeInterface $node) {
      return Markup::create('<a href="' . $node->toUrl()->toString() . '">' . $node->getTitle() . '</a>');
    }, $matching_nodes);

    if ($node_count === 1) {
      $this->messenger->addStatus(
        $this->t('A matching person profile was found for @node_link. You now have permission to edit it.', [
          '@node_link' => reset($node_links),
        ])
      );
    }
    else {
      $this->messenger->addStatus(
        $this->t('@count matching person profiles were found. You now have permission to edit: @node_links', [
          '@count' => $node_count,
          '@node_links' => Markup::create(implode(', ', $node_links)),
        ])
      );
    }
  }

  /**
   * Ensure the user has one of the required roles for content ownership.
   *
   * If the user doesn't have author, contributor, or site_manager role,
   * assign them the author role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  protected function ensureUserHasRequiredRole(AccountInterface $account): void {
    if ($this->hasRequiredRole($account)) {
      return;
    }

    // Load the user entity to modify roles.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($account->id());

    if ($user) {
      $user->addRole('author');
      $user->save();
    }
  }

  /**
   * Check if the user has any of the required roles for content ownership.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if the user has any required role, FALSE otherwise.
   */
  protected function hasRequiredRole(AccountInterface $account): bool {
    $user_roles = $account->getRoles();

    foreach (self::REQUIRED_ROLES as $role) {
      if (in_array($role, $user_roles)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
