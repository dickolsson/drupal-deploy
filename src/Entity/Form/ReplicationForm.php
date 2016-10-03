<?php

namespace Drupal\deploy\Entity\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\WorkspacePointerInterface;

/**
 * Form controller for Replication edit forms.
 *
 * @ingroup deploy
 */
class ReplicationForm extends ContentEntityForm {

  /** @var  WorkspacePointerInterface */
  protected $source = null;

  /** @var  WorkspacePointerInterface */
  protected $target = null;

  public function addTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    $this->setEntity(Replication::create());
    if (!$this->getDefaultSource() || !$this->getDefaultTarget()) {
      return $this->t('Error');
    }
    return $this->t('Deploy @source to @target', [
      '@source' => $this->getDefaultSource()->label(),
      '@target' => $this->getDefaultTarget()->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $js = isset($input['_drupal_ajax']) ? TRUE : FALSE;

    $form = parent::buildForm($form, $form_state);

    if (!$this->getDefaultSource() || !$this->getDefaultTarget()) {
      $message = 'Source and target must be set, make sure your current workspace has an upstream. Go to <a href=":path">this page</a> to edit your workspaces.';
      $message = $this->t($message, [':path' => Url::fromRoute('entity.workspace.collection')->toString()]);
      if ($js) {
        return ['#markup' => $message];
      }
      drupal_set_message($message, 'error');
      return [];
    }

    $form['source']['widget']['#default_value'] = [$this->getDefaultSource()->id()];

    if (empty($this->entity->get('target')->target_id) && $this->getDefaultTarget()) {
      $form['target']['widget']['#default_value'] = [$this->getDefaultTarget()->id()];
    }

    if (!$form['source']['#access'] && !$form['target']['#access']) {
      $form['actions']['submit']['#value'] = $this->t('Deploy to @target', ['@target' => $this->getDefaultTarget()->label()]);
    }
    else {
      $form['actions']['submit']['#value'] = $this->t('Deploy');
    }

    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'deploy'],
      'event' => 'mousedown',
      'prevent' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => 'Deploying',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $input = $form_state->getUserInput();
    $js = isset($input['_drupal_ajax']) ? TRUE : FALSE;

    try {
      $response = \Drupal::service('workspace.replicator_manager')->replicate(
        $this->entity->get('source')->entity,
        $this->entity->get('target')->entity
      );

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value === TRUE)) {
        $this->entity->set('replicated', REQUEST_TIME)->save();
        drupal_set_message('Successful deployment.');
      }
      else {
        drupal_set_message('Deployment error', 'error');
      }
    }
    catch(\Exception $e) {
      watchdog_exception('Deploy', $e);
      drupal_set_message($e->getMessage(), 'error');
    }

    if (!$js) {
      $form_state->setRedirect('entity.replication.collection');
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deploy(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.region-highlighted', \Drupal::service('renderer')->renderRoot($status_messages)));
    return $response;
  }

  protected function getDefaultSource() {
    if (!empty($this->source)) {
      return $this->source;
    }

    if (!empty($this->entity->get('source')) && ($this->entity->get('source')->entity instanceof WorkspacePointerInterface)) {
      return $this->source = $this->entity->get('source')->entity;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace ; * */
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    $workspace_pointers = \Drupal::service('entity_type.manager')
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    return $this->source = reset($workspace_pointers);
  }

  protected function getDefaultTarget() {
    if (!empty($this->target)) {
      return $this->target;
    }

    if (!empty($this->entity->get('target')) && ($this->entity->get('target')->entity instanceof WorkspacePointerInterface)) {
      return $this->target = $this->entity->get('target')->entity;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace ; * */
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    return $this->target = $workspace->get('upstream')->entity;
  }

}
