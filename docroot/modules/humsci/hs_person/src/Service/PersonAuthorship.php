<?php

namespace Drupal\hs_person\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
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
    $authname = $this->getUserAuthname($account);

    if (empty($user_email) && empty($authname)) {
      return 0;
    }

    $matching_nodes = $this->findMatchingPersonNodes($user_email, $authname, $account->id());
    if (empty($matching_nodes)) {
      return 0;
    }

    $updated_nodes = $this->assignNodesToUser($matching_nodes, $account, $user_email, $authname);

    if ($updated_nodes > 0) {
      $this->logAssignment($updated_nodes, $account, $user_email, $authname);
      $this->notifyUser($matching_nodes, $user_email, $authname);
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
   * Find hs_person nodes with matching SUNet IDs or email.
   *
   * @param string|null $user_email
   *   The email address to search for.
   * @param string|null $authname
   *   The user's authname (SUNet ID) to search for.
   * @param int $user_id
   *   The current user's ID to exclude nodes they already own.
   * @param int $limit
   *   Maximum number of nodes to return (default: 10).
   *
   * @return array
   *   Array of node entities that match the email or SUNet ID.
   */
  protected function findMatchingPersonNodes(?string $user_email, ?string $authname = NULL, int $user_id = 0, int $limit = 10): array {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'hs_person')
      ->condition('uid', $user_id, '<>');

    // Create an OR condition group for email or SUNet ID matching.
    $or_group = $query->orConditionGroup();

    // Add email condition only if email is not empty.
    if (!empty($user_email)) {
      $or_group->condition('field_hs_person_email', $user_email);
    }

    // Add SUNet ID condition if authname is available.
    if (!empty($authname)) {
      $or_group->condition('field_hs_person_sunet_id', $authname);
    }

    $query->condition($or_group);
    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    // Safety limit: only process the first N matching nodes.
    $total_matches = count($result);
    if ($total_matches > $limit) {
      $result = array_slice($result, 0, $limit);
      $this->loggerFactory->get('hs_person')->warning(
        'Found @total matches for user email "@email" authname "@authname", but limited processing to first @limit nodes. @skipped nodes were not processed.',
        [
          '@total' => $total_matches,
          '@email' => $user_email ?? 'none',
          '@authname' => $authname ?? 'none',
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
   * @param array $nodes
   *   Array of node entities to assign.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string|null $user_email
   *   The email address used for matching.
   * @param string|null $authname
   *   The user's authname (SUNet ID) used for matching.
   *
   * @return int
   *   The number of nodes that were successfully assigned.
   */
  protected function assignNodesToUser(array $nodes, AccountInterface $account, ?string $user_email, ?string $authname = NULL): int {
    $updated_count = 0;

    foreach ($nodes as $node) {
      if ($this->isNodeMatch($node, $user_email, $authname)) {
        $this->ensureUserHasRequiredRole($account);
        $this->assignNodeToUser($node, $account);
        $updated_count++;
      }
    }

    return $updated_count;
  }

  /**
   * Check if a node's email field or SUNet ID field matches the user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param string|null $user_email
   *   The email address to match against.
   * @param string|null $authname
   *   The authname (SUNet ID) to match against.
   *
   * @return bool
   *   TRUE if either the email or SUNet ID matches, FALSE otherwise.
   */
  protected function isNodeMatch($node, ?string $user_email, ?string $authname = NULL): bool {
    // Check email match only if email is not empty.
    $email_match = FALSE;
    if (!empty($user_email)) {
      $email_field = $node->get('field_hs_person_email');
      $email_match = !$email_field->isEmpty() && $email_field->value === $user_email;
    }

    // Check SUNet ID match if authname is available.
    $sunet_match = FALSE;
    if (!empty($authname)) {
      $sunet_field = $node->get('field_hs_person_sunet_id');
      $sunet_match = !$sunet_field->isEmpty() && $sunet_field->value === $authname;
    }

    return $email_match || $sunet_match;
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
   * @param string|null $user_email
   *   The email address used for matching.
   * @param string|null $authname
   *   The user's authname (SUNet ID) used for matching.
   */
  protected function logAssignment(int $count, AccountInterface $account, ?string $user_email, ?string $authname = NULL): void {
    $match_info = [];
    if (!empty($user_email)) {
      $match_info[] = "email: $user_email";
    }
    if (!empty($authname)) {
      $match_info[] = "SUNet ID: $authname";
    }
    $match_string = implode(', ', $match_info);

    $this->loggerFactory->get('hs_person')->info(
      'Updated @count anonymous-authored person node(s) to be owned by user @user_id (@match_info)',
      [
        '@count' => $count,
        '@user_id' => $account->id(),
        '@match_info' => $match_string,
      ]
    );
  }

  /**
   * Send notification messages to the user about assigned nodes.
   *
   * @param array $nodes
   *   Array of node entities that were assigned.
   * @param string|null $user_email
   *   The email address used for matching.
   * @param string|null $authname
   *   The user's authname (SUNet ID) used for matching.
   */
  protected function notifyUser(array $nodes, ?string $user_email, ?string $authname = NULL): void {
    $matching_nodes = array_filter($nodes, function ($node) use ($user_email, $authname) {
      return $this->isNodeMatch($node, $user_email, $authname);
    });

    if (empty($matching_nodes)) {
      return;
    }

    $node_count = count($matching_nodes);
    $node_links = array_map(function ($node) {
      return Markup::create('<a href="' . $node->toUrl()->toString() . '">' . $node->getTitle() . '</a>');
    }, $matching_nodes);

    if ($node_count === 1) {
      $this->messenger->addStatus(
        t('A matching person profile was found for @node_link. You now have permission to edit it.', [
          '@node_link' => reset($node_links),
        ])
      );
    }
    else {
      $this->messenger->addStatus(
        t('@count matching person profiles were found. You now have permission to edit: @node_links', [
          '@count' => $node_count,
          '@node_links' => Markup::create(implode(', ', $node_links)),
        ])
      );
    }
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
  protected function getUserEmail(AccountInterface $account): ?string {
    // First, check if this is an SSO user by getting their authname.
    $authname = $this->getUserAuthname($account);
    if ($authname) {
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
   * Get the authname (SUNet ID) for a user account.
   *
   * @todo Update this method to use a service from stanford_samlauth when one
   *   becomes available, instead of directly querying the authmap table.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return string|null
   *   The authname or null if none found.
   */
  protected function getUserAuthname(AccountInterface $account) {
    // First, check if this is an SSO user by looking up authmap.
    $authname = $this->database->select('authmap', 'a')
      ->fields('a', ['authname'])
      ->condition('uid', $account->id())
      ->execute()
      ->fetchField();

    if ($authname && $this->isValidAuthname($authname)) {
      return $authname;
    }

    // No authname found.
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
