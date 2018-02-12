<?php

namespace Drupal\deploy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
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

    // When no upstream workspace pointer is set or when the user doesn't have
    // the permissions to deploy to the upstream, the access is forbidden.
    if (!$upstream_workspace_pointer || !$account->hasPermission('Deploy to ' . $upstream_workspace_pointer->label())) {
      $access = AccessResult::forbidden();
    }
    return $access;
  }

}
