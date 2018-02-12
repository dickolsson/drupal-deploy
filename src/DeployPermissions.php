<?php

namespace Drupal\deploy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspace\Entity\WorkspacePointer;

/**
 * Defines permissions for Deploy module.
 */
class DeployPermissions {
  use StringTranslationTrait;

  /**
   * Generates the permissions for workspace pointer entities.
   *
   * @return array
   *   The permissions for workspace pointer entities.
   */
  public function workspacePointerPermissions() {
    $permissions = [];
    $workspace_pointers = WorkspacePointer::loadMultiple();
    foreach ($workspace_pointers as $workspace_pointer) {
      $permissions['Deploy to ' . $workspace_pointer->label()] = [
        'title' => $this->t('Deploy to @workspace_pointer', ['@workspace_pointer' => $workspace_pointer->label()]),
      ];
    }
    return $permissions;
  }

}
