<?php

namespace Drupal\mrc_helper\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to handle dynamic entity autocomplete.
 */
class DynamicEntityAutocompleteController extends ControllerBase {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var \Drupal\Core\Entity\EntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Constructs a EntityAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $matcher
   *   The autocomplete matcher for entity references.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value factory.
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.autocomplete_matcher'),
      $container->get('keyvalue')->get('dynamic_entity_autocomplete')
    );
  }

  /**
   * Handle the dymamic autocomplete route.
   */
  public function handleAutocomplete(Request $request, $selection_settings_key) {
    if (!$input = $request->query->get('q')) {
      throw new \Exception('No input given to autocomplete route.');
    }

    $typed_string = Tags::explode($input);
    $typed_string = Unicode::strtolower(array_pop($typed_string));
    $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);

    $combined_matches = [];

    foreach ($selection_settings as $entity_type_id => $settings) {
      $matches = $this->matcher->getMatches($entity_type_id, $settings['selection_handler'], $settings['selection_settings'], $typed_string);
      foreach ($matches as $match) {
        // Trim quotes since commas break this.
        // @see \Drupal\Core\Entity\EntityAutocompleteMatcher::getMatches()
        preg_match('/(?<entity_id>\d+)\)$/', trim($match['value'], '"'), $matches);
        $combined_matches[] = [
          'label' => $match['label'],
          'value' => sprintf('%s (%s:%s)', $match['label'], $entity_type_id, $matches['entity_id']),
        ];
      }
    }

    return new JsonResponse($combined_matches);
  }

}
