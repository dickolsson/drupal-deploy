<?php

namespace Drupal\deploy\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\workspace\Entity\Replication;

class ReplicationActionForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);

    $form['#weight'] = 9999;
    $form['replication_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id()
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $entity->get('replicated')->value ? $this->t('Re-deploy') : $this->t('Deploy'),
    );
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'replication_action';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);
    /** @var ReplicationLogInterface $response */
    $response = \Drupal::service('workspace.replicator_manager')->replicate(
        $entity->get('source')->entity,
        $entity->get('target')->entity
      );
    if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
      $entity->set('replicated', REQUEST_TIME)->save();
      drupal_set_message('Successful deployment.');
    }
    else {
      drupal_set_message('Deployment error. Check recent log messages for more details.', 'error');
    }
  }

  protected function getEntity(FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];
    /** @var Replication $entity */
    $entity = $args[0];
    if ($entity instanceof Replication) {
      return $entity;
    }
    throw new EntityMalformedException('Invalid Replication entity given.');
  }

}
