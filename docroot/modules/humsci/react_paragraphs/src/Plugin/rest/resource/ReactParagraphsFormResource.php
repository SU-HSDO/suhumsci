<?php

namespace Drupal\react_paragraphs\Plugin\rest\resource;

use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "react_paragraphs_form_resource",
 *   label = @Translation("React paragraphs form resource"),
 *   uri_paths = {
 *     "canonical" = "/api/forms/{paragraph_type}"
 *   }
 * )
 */
class ReactParagraphsFormResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ReactParagraphsFormResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('react_paragraphs'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param string $paragraph_type
   *   The entity object.
   *
   * @return JsonResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($paragraph_type) {
    if ((int) $paragraph_type > 0) {
      $entity = Paragraph::load($paragraph_type);
    }
    else {
      $entity = Paragraph::create(['type' => $paragraph_type]);
      foreach(array_keys($entity->getFields()) as $field_name){
        $entity->set($field_name, '');
      }
    }

    return new JsonResponse($entity);
  }

  protected function getFormElements(array $form, &$elements = []) {
    foreach (Element::children($form) as $item_key) {
      $elements[$item_key] = $form[$item_key];
      if (is_array($form[$item_key])) {
        $this->getFormElements($form[$item_key], $elements[$item_key]);
      }
    }
    return $elements;
  }

}
