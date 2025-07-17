<?php

namespace Drupal\hs_person\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service for handling person node authorship assignment.
 *
 * This service handles the business logic for:
 * - Determining user email addresses (SSO authname + domain or direct email.)
 * - Finding anonymous-authored hs_person nodes with matching email.
 * - Assigning ownership of matching nodes to users.
 * - Ensuring users have appropriate roles for content ownership.
 * - Notifying users of assigned content.
 */
class PersonAuthorship {

  /**
   * Required roles for content ownership.
   */
  private const REQUIRED_ROLES = ['author', 'contributor', 'site_manager'];

  /**
   * SSO email domain suffix.
   */
  private const SSO_DOMAIN = '@stanford.edu';

  /**
   * Regular expression pattern for valid authname characters.
   */
  private const AUTHNAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

  /**
   * Anonymous user ID.
   */
  private const ANONYMOUS_UID = 0;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new PersonAuthorship object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    Connection $database,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->database = $database;
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

    $user_email = $this->getUserEmail($account);
    if (empty($user_email)) {
      return 0;
    }

    $matching_nodes = $this->findMatchingPersonNodes($user_email);
    if (empty($matching_nodes)) {
      return 0;
    }

    $updated_nodes = $this->assignNodesToUser($matching_nodes, $account, $user_email);

    if ($updated_nodes > 0) {
      $this->logAssignment($updated_nodes, $account, $user_email);
      $this->notifyUser($matching_nodes, $user_email);
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
    return $account->isAnonymous();
  }

  /**
   * Find anonymous-authored hs_person nodes with matching email.
   *
   * @param string $user_email
   *   The email address to search for.
   *
   * @return array
   *   Array of node entities that match the email.
   */
  protected function findMatchingPersonNodes(string $user_email): array {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_person')
      ->condition('uid', self::ANONYMOUS_UID)
      ->condition('status', 1)
      ->condition('field_hs_person_email', $user_email)
      ->execute();

    if (empty($query)) {
      return [];
    }

    return $node_storage->loadMultiple($query);
  }

  /**
   * Assign nodes to the user and ensure they have required roles.
   *
   * @param array $nodes
   *   Array of node entities to assign.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $user_email
   *   The email address used for matching.
   *
   * @return int
   *   The number of nodes that were successfully assigned.
   */
  protected function assignNodesToUser(array $nodes, AccountInterface $account, string $user_email): int {
    $updated_count = 0;

    foreach ($nodes as $node) {
      if ($this->isNodeEmailMatch($node, $user_email)) {
        $this->ensureUserHasRequiredRole($account);
        $this->assignNodeToUser($node, $account);
        $updated_count++;
      }
    }

    return $updated_count;
  }

  /**
   * Check if a node's email field matches the user email.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param string $user_email
   *   The email address to match against.
   *
   * @return bool
   *   TRUE if the email matches, FALSE otherwise.
   */
  protected function isNodeEmailMatch($node, string $user_email): bool {
    $email_field = $node->get('field_hs_person_email');
    return !$email_field->isEmpty() && $email_field->value === $user_email;
  }

  /**
   * Assign a single node to a user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to assign.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  protected function assignNodeToUser($node, AccountInterface $account): void {
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
   * @param string $user_email
   *   The email address used for matching.
   */
  protected function logAssignment(int $count, AccountInterface $account, string $user_email): void {
    $this->loggerFactory->get('hs_person')->info(
      'Updated @count anonymous-authored person node(s) to be owned by user @user_id (@email)',
      [
        '@count' => $count,
        '@user_id' => $account->id(),
        '@email' => $user_email,
      ]
    );
  }

  /**
   * Send notification messages to the user about assigned nodes.
   *
   * @param array $nodes
   *   Array of node entities that were assigned.
   * @param string $user_email
   *   The email address used for matching.
   */
  protected function notifyUser(array $nodes, string $user_email): void {
    foreach ($nodes as $node) {
      if ($this->isNodeEmailMatch($node, $user_email)) {
        $this->addNodeNotification($node);
      }
    }
  }

  /**
   * Add a notification message for a specific node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to create a notification for.
   */
  protected function addNodeNotification($node): void {
    $node_title = $node->getTitle();
    $node_url = $node->toUrl()->toString();

    $this->messenger->addStatus(
      t('A matching person profile was found for <a href="@url">@title</a>. You now have permission to edit it.', [
        '@url' => $node_url,
        '@title' => $node_title,
      ])
    );
  }

  /**
   * Get the email address for a user account.
   *
   * For SSO users, look up their authmap.authname and appends the SSO domain.
   * For regular users, this returns their email address as a fallback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return string|null
   *   The email address or null if none found.
   */
  protected function getUserEmail(AccountInterface $account) {
    // First, check if this is an SSO user by looking up authmap.
    $authname = $this->database->select('authmap', 'a')
      ->fields('a', ['authname'])
      ->condition('uid', $account->id())
      ->execute()
      ->fetchField();

    if ($authname && $this->isValidAuthname($authname)) {
      $sso_email = $authname . self::SSO_DOMAIN;
      return $sso_email;
    }

    // If no SSO authname, try to get the user's email address directly.
    $email = $account->getEmail();
    if (!empty($email)) {
      return $email;
    }

    // No email found.
    return NULL;
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
  protected function ensureUserHasRequiredRole(AccountInterface $account) {
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
   * Check if the authname contains only valid characters.
   *
   * Valid characters are: alphanumeric, hyphens (-), and underscores (_).
   *
   * @param string $authname
   *   The authname to validate.
   *
   * @return bool
   *   TRUE if the authname is valid, FALSE otherwise.
   */
  protected function isValidAuthname(string $authname): bool {
    return preg_match(self::AUTHNAME_PATTERN, $authname);
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
