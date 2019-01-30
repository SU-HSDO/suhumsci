<?php

namespace Drupal\hs_actions\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Clones a node.
 *
 * @Action(
 *   id = "node_clone_action",
 *   label = @Translation("Clone selected content"),
 *   type = "node"
 * )
 */
class CloneNode extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'clone_count' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $values = range(1, 10);
    $form['clone_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Clone how many times'),
      '#options' => array_combine($values, $values),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['clone_count'] = $form_state->getValue('clone_count');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->getOwner()->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    for ($i = 0; $i < $this->configuration['clone_count']; $i++) {
      $duplicate = $entity->createDuplicate();
      $duplicate->save();
    }
  }

}
