<?php

namespace Drupal\deploy\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\WorkspacePointerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ReplicationActionForm class.
 */
class ReplicationActionForm extends FormBase {

  /**
   * The injected service to track conflicts during replication.
   *
   * @var \Drupal\multiversion\Workspace\ConflictTrackerInterface
   */
  protected $conflictTracker;

  /**
   * A source object.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $source = NULL;

  /**
   * A target object.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $target = NULL;

  /**
   * The Entity Type Plug-in Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Workspace Plug-in Manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\multiversion\Workspace\ConflictTrackerInterface $conflict_tracker
   *   The conflict tracking service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type plug-in manager.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(ConflictTrackerInterface $conflict_tracker, EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager) {
    $this->conflictTracker = $conflict_tracker;
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.conflict_tracker'),
      $container->get('entity_type.manager'),
      $container->get('workspace.manager')
    );
  }

  /**
   * Implements buildForm().
   *
   * {@inheritDoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);

    $form['#weight'] = 9999;
    $form['replication_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id(),
    ];

    // Allow the user to not abort on conflicts.
    if ($default_source = $this->getDefaultSource($form_state)) {
      $source_workspace = $default_source->getWorkspace();
    }

    if ($default_target = $this->getDefaultTarget($form_state)) {
      $target_workspace = $default_target->getWorkspace();
    }
    $conflicts = $this->conflictTracker
      ->useWorkspace($source_workspace)
      ->getAll();
    if ($conflicts) {
      $form['message'] = $this->generateMessageRenderArray('error', $this->t(
        'There are <a href=":link">@count conflict(s) with the :target workspace</a>. Pushing changes to :target may result in unexpected behavior or data loss, and cannot be undone. Please proceed with       caution.',
        [
          '@count' => count($conflicts),
          ':link' => Url::fromRoute('entity.workspace.conflicts', ['workspace' => $source_workspace->id()])->toString(),
          ':target' => $this->getDefaultTarget($form_state)->label(),
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



    if ($source_workspace && !$source_workspace->isPublished() && $target_workspace && !$target_workspace->isPublished()) {
      $message = $this->t('This deployment cannot be re-deployed because both source workspace (%source) and target workspace (%target) have been archived.',
        [
          '%source' => $source_workspace->label(),
          '%target' => $target_workspace->label(),
        ]
      );
    }
    elseif (!$default_source || ($source_workspace && !$source_workspace->isPublished())) {
      $message = $this->t('This deployment cannot be re-deployed because the source workspace %source has been archived.', ['%source' => $source_workspace ? '(' . $source_workspace->label() . ')' : '']);
    }
    elseif (!$default_target || ($target_workspace && !$target_workspace->isPublished())) {
      $message = $this->t('This deployment cannot be re-deployed because the target workspace %target has been archived.', ['%target' => $target_workspace ? '(' . $target_workspace->label() . ')' : '']);
    }

    if (isset($message)) {
      $form['message'] = $this->generateMessageRenderArray('warning', $message);
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $entity->get('replicated')->value ? $this->t('Re-deploy') : $this->t('Deploy'),
      ];
    }

    return $form;
  }

  /**
   * Implements getFormId().
   *
   * {@inheritDoc}.
   */
  public function getFormId() {
    return 'replication_action';
  }

  /**
   * Implements submitForm().
   *
   * {@inheritDoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Pass the abort flag to the ReplicationManager using runtime-only state,
    // i.e. a static.
    // @see \Drupal\workspace\ReplicatorManager
    // @see \Drupal\workspace\Entity\Form\WorkspaceForm
    $is_aborted_on_conflict = !$form_state->hasValue('is_aborted_on_conflict') || $form_state->getValue('is_aborted_on_conflict') === 'true';
    drupal_static('workspace_is_aborted_on_conflict', $is_aborted_on_conflict);

    $entity = $this->getEntity($form_state);
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $response */
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

  /**
   * Returns the entity object itself or an EntityMalformedException if error.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State values.
   *
   * @return \Drupal\workspace\Entity\Replication
   *   The expected parent class for $entity.
   *
   * @throws EntityMalformedException
   */
  protected function getEntity(FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];
    /** @var \Drupal\workspace\Entity\Replication $entity */
    $entity = $args[0];
    if ($entity instanceof Replication) {
      return $entity;
    }
    throw new EntityMalformedException('Invalid Replication entity given.');
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

  /**
   * Returns a source object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State values.
   *
   * @return mixed
   *   The source object itself.
   */
  protected function getDefaultSource(FormStateInterface $form_state) {
    if (!empty($this->source)) {
      return $this->source;
    }

    if (!empty($this->getEntity($form_state)->get('source')) && ($this->getEntity($form_state)->get('source')->entity instanceof WorkspacePointerInterface)) {
      return $this->source = $this->getEntity($form_state)->get('source')->entity;
    }
  }

  /**
   * Returns a target object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State values.
   *
   * @return mixed
   *   The target object itself.
   */
  protected function getDefaultTarget(FormStateInterface $form_state) {
    if (!empty($this->target)) {
      return $this->target;
    }

    if (!empty($this->getEntity($form_state)->get('target')) && ($this->getEntity($form_state)->get('target')->entity instanceof WorkspacePointerInterface)) {
      return $this->target = $this->getEntity($form_state)->get('target')->entity;
    }
  }

}
