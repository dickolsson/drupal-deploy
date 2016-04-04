<?php

/**
 * @file
 * Deploy views revision status field handler.
 */

/**
 * Class for handling the deploy manager entity revision status "field".
 */
class deploy_views_handler_revision_status_field extends deploy_views_handler_base {

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    $entity = $this->entity_load($values);
    $status = t('Latest');
    if (!deploy_is_latest_revision($values->entity_type, $entity)) {
      $status = t('Newer Available');
    }
    return $status;
  }
}
