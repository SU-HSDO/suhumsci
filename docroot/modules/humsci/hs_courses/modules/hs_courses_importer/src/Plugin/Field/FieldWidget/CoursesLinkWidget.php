<?php

namespace Drupal\hs_courses_importer\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "explore_courses_link",
 *   label = @Translation("Explore Courses Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class CoursesLinkWidget extends LinkWidget {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() == 'field_course_url';
  }

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['uri']['#element_validate'][] = [$this, 'validateCourseUrl'];
    return $element;
  }

  /**
   * Validate we have a legit url.
   *
   * @param array $element
   *   Url form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state object.
   * @param array $complete_form
   *   Complete form.
   */
  public function validateCourseUrl(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $url = UrlHelper::parse($element['#value']);
    if (!empty($url['path']) && !str_contains($url['path'], 'explorecourses')) {
      $form_state->setError($element, $this->t('The URL is not a valid ExploreCourses URL.'));
    }
  }

  /**
   * {@inheritDoc}.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$value) {
      if (!empty($value['uri'])) {
        // Parse the existing URL.
        $url = UrlHelper::parse($value['uri']);
        $url['query']['view'] = 'xml-20200810';
        $massaged_url = Url::fromUri($url['path'], ['query' => $url['query']]);
        $values[$delta]['uri'] = $massaged_url->toString();
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
