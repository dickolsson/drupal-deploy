<?php

/**
 * @file
 * Deploy Views base "field" handler.
 */

/**
 * Base class for handling the Deploy Manager "fields",
 */
abstract class deploy_views_handler_base extends views_handler_field {

  /**
   * {@inheritdoc}
   */
  function construct() {
    parent::construct();
    // We have to set this here otherwise the parent construcutor overrides it.
    $this->additional_fields += [
      'plan_name' => 'plan_name',
      'entity_type' => 'entity_type',
      'entity_id' => 'entity_id',
      'revision_id' => 'revision_id'
    ];
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->add_additional_fields();
  }

  /**
   * Loads an entity based on the row values.
   *
   * @param object $values
   *   The views result row values.
   *
   * @return object
   *   The entity object.
   */
  public function entity_load($values) {
    $entity = deploy_plan_entity_load($values->entity_type, $values->entity_id, $values->revision_id);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    return t('Not Implemented');
    // This is overridden in the concrete implementations.
  }
}
