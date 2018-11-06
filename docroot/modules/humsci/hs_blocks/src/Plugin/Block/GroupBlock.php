<?php

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\block_content\Access\RefinableDependentAccessInterface;
use Drupal\block_content\Access\RefinableDependentAccessTrait;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines an inline block plugin type.
 *
 * @Block(
 *   id = "group_block",
 *   admin_label = @Translation("Group block"),
 *   category = @Translation("Inline blocks"),
 *   deriver = "\Drupal\hs_blocks\Plugin\Derivative\GroupBlockDeriver"
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class GroupBlock extends BlockBase implements ContainerFactoryPluginInterface, RefinableDependentAccessInterface {

  use RefinableDependentAccessTrait;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Uuid Service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Constructs a new InlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repo
   *   Context repository service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   Uuid Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ContextRepositoryInterface $context_repo, UuidInterface $uuid_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->contextRepository = $context_repo;
    $this->uuidGenerator = $uuid_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('context.repository'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['machine_name' => NULL, 'children' => []];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $components = $this->getComponents();
    // This prevents the block label from displaying if there are no contents.
    if (empty(render($components))) {
      return AccessResult::forbidden();
    }
    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['components'] = $this->getComponents((bool) $this->getSectionStorage());
    // Set the cache keys so that each block will have its own cache, even if
    // it has the same machine name on different entity displays.
    $build['#cache']['keys'] = array_keys($build['components']);
    return $build;
  }

  /**
   * Get the components for the group block.
   *
   * @param bool $in_preview
   *   In preview and add administrative tools.
   *
   * @return array
   *   Render array of components.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getComponents($in_preview = FALSE) {
    $components = [];

    // Pass the contexts from the block into the component.
    $contexts = $this->contextRepository->getAvailableContexts();
    $contexts['layout_builder.entity'] = $this->getContext('entity');

    // Build the render array for each component.
    foreach ($this->configuration['children'] as $uuid => $child) {
      $component = new SectionComponent($uuid, 'content', $child);
      $components[$uuid] = $component->toRenderArray($contexts);
    }

    // Add administrative links.
    if ($in_preview) {
      $this->buildAdministrativeSection($components);
    }
    return $components;
  }

  /**
   * Adds contextual links and the add more button to components.
   *
   * @param array $components
   *   Render array of components.
   */
  protected function buildAdministrativeSection(array &$components) {
    $section_storage = $this->getSectionStorage();
    $section_delta = $this->getSectionDelta($section_storage);
    foreach (array_keys($components) as $uuid) {
      $components[$uuid]['#contextual_links'] = [
        'hs_blocks_block' => [
          'route_parameters' => [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $section_delta,
            'group' => $this->configuration['machine_name'],
            'uuid' => $uuid,
          ],
        ],
      ];
    }

    $components['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Block to Group'),
      '#url' => Url::fromRoute('hs_blocks.choose_block',
        [
          'section_storage_type' => $section_storage->getStorageType(),
          'section_storage' => $section_storage->getStorageId(),
          'delta' => $section_delta,
          'group' => $this->configuration['machine_name'],
        ],
        [
          'attributes' => [
            'class' => ['use-ajax', 'new-block__link'],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'off_canvas',
          ],
        ]
      ),
    ];
  }

  /**
   * Get the current section storage object if on the administrative pages.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   Storage object.
   */
  protected function getSectionStorage() {
    return $this->requestStack->getCurrentRequest()->attributes->get('section_storage');
  }

  /**
   * Find which delta of the current layout builder this block is in.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   Layout Builder Section Storage.
   *
   * @return int
   *   Section delta.
   */
  protected function getSectionDelta(SectionStorageInterface $section_storage) {
    $delta = 0;

    /** @var \Drupal\layout_builder\Section $section */
    foreach ($section_storage->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $component_config = $component->get('configuration');
        list($component_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $component_config['id']);

        // We found the delta, so send it back.
        if ($component_id == 'group_block' && isset($component_config['machine_name']) && $component_config['machine_name'] == $this->configuration['machine_name']) {
          return $delta;
        }
      }

      $delta++;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Set the machine name to a uuid value only if its a new block.
    if (!$form_state->getErrors() && empty($this->configuration['machine_name'])) {
      $this->setUniqueUuid();
    }
  }

  /**
   * Set the current block to a unique UUID within the current section storage.
   */
  protected function setUniqueUuid() {
    $this->configuration['machine_name'] = $this->uuidGenerator->generate();
    while (!is_null($this->getSectionDelta($this->getSectionStorage()))) {
      $this->configuration['machine_name'] = $this->uuidGenerator->generate();
    }
  }

}
