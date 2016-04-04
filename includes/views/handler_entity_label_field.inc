<?php

/**
 * @file
 * Deploy Views Entity Label field handler.
 */

/**
 * Class for handling the Deploy Manager Entity Label "field",
 */
class deploy_views_handler_entity_label_field extends deploy_views_handler_base {

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    $entity = $this->entity_load($values);
    $label = entity_label($values->entity_type, $entity);
    return $label;
  }
}
