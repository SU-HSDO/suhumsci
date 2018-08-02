<?php

namespace Drupal\mrc_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\mrc_helper\Element\DynamicEntityAutocomplete;

/**
 * Overrides the default core link widget.
 *
 * Remove when https://www.drupal.org/project/drupal/issues/2423093 is resolved.
 *
 * @FieldWidget(
 *   id = "link_default",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkFieldWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if ($element['uri']['#type'] != 'entity_autocomplete') {
      return $element;
    }

    $element['uri']['#type'] = 'dynamic_entity_autocomplete';

    $target_types = [
      'node' => [
        'selection_handler' => 'default',
        'selection_settings' => [],
      ],
      'taxonomy_term' => [
        'selection_handler' => 'default',
        'selection_settings' => [],
      ],
    ];
    $element['uri']['#target_types'] = $target_types;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;
    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);

    if ($entity !== NULL && strpos($entity, ':') !== FALSE) {
      list($entity_type, $entity_id) = explode(':', $entity);
      $uri = "entity:$entity_type/$entity_id";
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }
    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);

      if (in_array($entity_type, [
          'node',
          'taxonomy_term',
        ]) && $entity = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($entity_id)) {
        $displayable_string = DynamicEntityAutocomplete::getEntityLabels([$entity]);
      }
    }
    // Trim quotes since commas break this.
    // @see \Drupal\Core\Entity\EntityAutocompleteMatcher::getMatches()
    return trim($displayable_string, '"');
  }

}
