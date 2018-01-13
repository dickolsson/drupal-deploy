<?php

namespace Drupal\deploy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * ReplicationAccessControlHandler class.
 */
class ReplicationAccessControlHandler extends EntityAccessControlHandler {

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

}
