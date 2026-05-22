<?php

namespace Drupal\Tests\hs_rabbit_redirect_no_edit\Kernel\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the "Page redirect (skip for editors)" Rabbit Hole behavior.
 *
 * @coversDefaultClass \Drupal\hs_rabbit_redirect_no_edit\Plugin\RabbitHoleBehaviorPlugin\PageRedirectNoEdit
 * @group hs_rabbit_redirect_no_edit
 */
class PageRedirectNoEditTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'rabbit_hole',
    'rh_node',
    'hs_rabbit_redirect_no_edit',
  ];

  /**
   * The plugin under test.
   *
   * @var \Drupal\hs_rabbit_redirect_no_edit\Plugin\RabbitHoleBehaviorPlugin\PageRedirectNoEdit
   */
  protected $plugin;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'node', 'rabbit_hole']);

    // Create a node type and configure rabbit hole bundle settings to use the
    // new behavior plugin.
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    /** @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface $settings_manager */
    $settings_manager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $settings_manager->saveBehaviorSettings([
      'action' => 'page_redirect_no_edit',
      'allow_override' => TRUE,
      'redirect' => 'https://example.com/redirected',
      'redirect_code' => 302,
      'redirect_fallback_action' => 'access_denied',
    ], 'node_type', 'page');

    // Ensure user 1 is not used as the test user (uid 1 bypasses access).
    User::create([
      'uid' => 1,
      'name' => 'admin',
      'status' => 1,
    ])->save();

    $this->node = Node::create([
      'type' => 'page',
      'title' => 'Test page',
      'status' => 1,
      'uid' => 1,
    ]);
    $this->node->save();

    $this->plugin = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin')
      ->createInstance('page_redirect_no_edit');

    $this->accountSwitcher = $this->container->get('account_switcher');
  }

  /**
   * Anonymous users should be redirected.
   *
   * @covers ::performAction
   */
  public function testAnonymousUserIsRedirected() {
    $this->accountSwitcher->switchTo(new AnonymousUserSession());

    // Re-create the plugin so it picks up the switched current_user.
    $plugin = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin')
      ->createInstance('page_redirect_no_edit');

    $response = $plugin->performAction($this->node);

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertStringContainsString('example.com/redirected', $response->getTargetUrl());
    $this->assertContains('user.permissions', $response->getCacheableMetadata()->getCacheContexts());
    $this->assertEmpty($this->getStatusMessages());

    $this->accountSwitcher->switchBack();
  }

  /**
   * Users with edit access should not be redirected.
   *
   * @covers ::performAction
   */
  public function testEditorIsNotRedirected() {
    $editor = $this->createUserWithPermissions(['edit any page content', 'access content']);
    $this->accountSwitcher->switchTo($editor);

    $plugin = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin')
      ->createInstance('page_redirect_no_edit');

    $response = $plugin->performAction($this->node);

    // performAction returns NULL so the canonical page renders unchanged. The
    // accompanying status message is added by
    // hs_rabbit_redirect_no_edit_entity_view(), not by performAction itself.
    $this->assertNull($response);

    $this->accountSwitcher->switchBack();
  }

  /**
   * Authenticated users without edit access should still be redirected.
   *
   * @covers ::performAction
   */
  public function testNonEditorIsRedirected() {
    $user = $this->createUserWithPermissions(['access content']);
    $this->accountSwitcher->switchTo($user);

    $plugin = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin')
      ->createInstance('page_redirect_no_edit');

    $response = $plugin->performAction($this->node);

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertStringContainsString('example.com/redirected', $response->getTargetUrl());
    $this->assertEmpty($this->getStatusMessages());

    $this->accountSwitcher->switchBack();
  }

  /**
   * With no target configured, fall through to parent's fallback handling.
   *
   * @covers ::performAction
   */
  public function testEmptyTargetFallsThroughToParent() {
    /** @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface $settings_manager */
    $settings_manager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $settings_manager->saveBehaviorSettings([
      'action' => 'page_redirect_no_edit',
      'allow_override' => TRUE,
      'redirect' => '',
      'redirect_code' => 302,
      'redirect_fallback_action' => 'access_denied',
    ], 'node_type', 'page');

    $editor = $this->createUserWithPermissions(['edit any page content', 'access content']);
    $this->accountSwitcher->switchTo($editor);

    $plugin = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin')
      ->createInstance('page_redirect_no_edit');

    $response = $plugin->performAction($this->node);

    // With no target the override does not short-circuit; parent returns its
    // configured fallback action (a string), so no status message is added.
    $this->assertEmpty($this->getStatusMessages());
    $this->assertNotInstanceOf(TrustedRedirectResponse::class, $response);

    $this->accountSwitcher->switchBack();
  }

  /**
   * Creates a user with the given permissions on the authenticated role.
   *
   * @param string[] $permissions
   *   The permissions to grant.
   *
   * @return \Drupal\user\UserInterface
   *   The new user.
   */
  protected function createUserWithPermissions(array $permissions) {
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => 'role_' . $this->randomMachineName(8),
      'label' => 'Test role ' . $this->randomMachineName(4),
    ]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();

    $user = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
      'roles' => [$role->id()],
    ]);
    $user->save();
    return $user;
  }

  /**
   * Returns and clears any status messages on the messenger.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]|string[]
   *   Status messages.
   */
  protected function getStatusMessages(): array {
    $messenger = $this->container->get('messenger');
    $all = $messenger->all();
    $messenger->deleteAll();
    return $all['status'] ?? [];
  }

}
