<?php

namespace Drupal\deploy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ReplicationAccessControlHandler class.
 */
class ReplicationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The workspace manager service.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a NodeAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, WorkspaceManagerInterface $workspace_manager) {
    parent::__construct($entity_type);
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('workspace.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $restricted_fields = ['source', 'target'];
    if (in_array($field_definition->getName(), $restricted_fields)) {
      return AccessResult::forbidden();
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $entity_bundle);
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    $upstream_workspace_pointer = $active_workspace->upstream->entity;

    // When no upstream workspace pointer is set the access is forbidden.
    if (!$upstream_workspace_pointer) {
      return AccessResult::forbidden('No target is set for the active workspace.');
    }

    // The 'deploy to any workspace' permission will always allow the user to
    // create replication entities and perform deployments.
    if ($account->hasPermission('deploy to any workspace')) {
      return AccessResult::allowed();
    }

    // Load just the ID and workspace separately to allow for remote workspace
    // pointers which won't have the workspace_pointer field set.
    $upstream_workspace_id = $upstream_workspace_pointer->workspace_pointer->target_id;
    $upstream_workspace = Workspace::load($upstream_workspace_id);
    // When the upstream workspace is set, the owner matches the account, and
    // the user has the correct permission then allow access.
    if ($upstream_workspace && $upstream_workspace->getOwnerId() == $account->id() && $account->hasPermission('deploy to own workspace')) {
      return AccessResult::allowed();
    }

    // When the user doesn't have permissions to deploy to the upstream the
    // access is forbidden.
    if (!$account->hasPermission('Deploy to ' . $upstream_workspace_pointer->label())) {
      return AccessResult::forbidden('You do not have permission to deploy to the target.');
    }

    return $access;
  }

}
