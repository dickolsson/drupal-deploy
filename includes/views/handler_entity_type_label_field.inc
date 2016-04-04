<?php

/**
 * @file
 * Deploy Views Entity Type Label field handler.
 */

/**
 * Class for handling the Deploy Manager Entity Type Label "field",
 */
class deploy_views_handler_entity_type_label_field extends views_handler_field {

  /**
   * {@inheritdoc}
   */
  function construct() {
    parent::construct();
    // We have to set this here otherwise the parent construcutor overrides it.
    $this->additional_fields += [
      'entity_type' => 'entity_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->add_additional_fields();
  }

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    $info = entity_get_info($values->entity_type);
    return $info['label'];
  }
}
