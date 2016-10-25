<?php

namespace Drupal\deploy\Entity\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
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

  public function addTitle() {
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

    // @todo Move this to be injected.
    $this->conflictTracker = \Drupal::service('workspace.conflict_tracker');

    // Allow the user to not abort on conflicts.
    $source_workspace = $this->getDefaultSource()->getWorkspace();
    $target_workspace = $this->getDefaultTarget()->getWorkspace();
    $conflicts = $this->conflictTracker
      ->useWorkspace($source_workspace)
      ->getAll();
    if ($conflicts) {
      $form['message'] = $this->generateMessageRenderArray('error', $this->t(
        'There are <a href=":link">@count conflict(s) with the :target workspace</a>. Pushing changes to :target may result in unexpected behavior or data loss, and cannot be undone. Please proceed with       caution.',
        [
          '@count' => count($conflicts),
          ':link' => Url::fromRoute('entity.workspace.conflicts', ['workspace' => $source_workspace->id()])->toString(),
          ':target' => $target_workspace->label(),
        ]
      ));
      $form['is_aborted_on_conflict'] = [
        '#type' => 'radios',
        '#title' => $this->t('Abort if there are conflicts?'),
        '#default_value' => 'true',
        '#options' => [
          'true' => $this->t('Yes, if conflicts are found do not replicate to upstream.'),
          'false' => $this->t('No, go ahead and push any conflicts to the upstream.'),
        ],
        '#weight' => 0,
      ];
    }
    else {
      $form['message'] = $this->generateMessageRenderArray('status', 'There are no conflicts.');
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
    // Pass the abort flag to the ReplicationManager using runtime-only state,
    // i.e. a static.
    // @see \Drupal\workspace\ReplicatorManager
	  // @see \Drupal\workspace\Entity\Form\WorkspaceForm
    $is_aborted_on_conflict = !$form_state->hasValue('is_aborted_on_conflict') || $form_state->getValue('is_aborted_on_conflict') === 'true';
    drupal_static('workspace_is_aborted_on_conflict', $is_aborted_on_conflict);

    parent::save($form, $form_state);

    $input = $form_state->getUserInput();
    $js = isset($input['_drupal_ajax']) ? TRUE : FALSE;

    try {
      $response = \Drupal::service('workspace.replicator_manager')->replicate(
        $this->entity->get('source')->entity,
        $this->entity->get('target')->entity
      );

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
        $this->entity->set('replicated', REQUEST_TIME)->save();
        drupal_set_message('Successful deployment.');
      }
      else {
        drupal_set_message('Deployment error. Check recent log messages for more details.', 'error');
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
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deploy() {
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

    /** @var \Drupal\multiversion\Entity\Workspace $workspace ; */
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

    /** @var \Drupal\multiversion\Entity\Workspace $workspace ; */
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    return $this->target = $workspace->get('upstream')->entity;
  }

  /**
   * Generate a message render array with the given text.
   *
   * @param string $type
   *   The type of message: status, warning, or error.
   * @param string $message
   *   The message to create with.
   *
   * @return array
   *   The render array for a status message.
   *
   * @see \Drupal\Core\Render\Element\StatusMessages
   */
  protected function generateMessageRenderArray($type, $message) {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        $type => [Markup::create($message)],
      ],
    ];
  }

}
