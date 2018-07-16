<?php

namespace Drupal\hs_courses_importer\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CourseTagForm.
 */
class CourseTagForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\hs_courses_importer\Entity\CourseTagInterface $hs_course_tag */
    $hs_course_tag = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Explore Courses Tag'),
      '#maxlength' => 255,
      '#default_value' => $hs_course_tag->label(),
      '#description' => $this->t("Machine tag name from explorecourses.stanford.edu."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $hs_course_tag->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hs_courses_importer\Entity\CourseTag::load',
      ],
      '#disabled' => !$hs_course_tag->isNew(),
    ];

    $form['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Translated Tag'),
      '#default_value' => $hs_course_tag->tag(),
      '#required' => true,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $hs_course_tag = $this->entity;
    $status = $hs_course_tag->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Course Tag Translation.', [
          '%label' => $hs_course_tag->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Course Tag Translation.', [
          '%label' => $hs_course_tag->label(),
        ]));
    }
    $form_state->setRedirectUrl($hs_course_tag->toUrl('collection'));
  }

}
