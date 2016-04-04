<?php

/**
 * @file
 * Developer Manager Entities Entity.
 */

/**
 * Developer Manager Entities Entity class.
 */
class DeployManagerEntitiesEntity extends Entity {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $entity = deploy_plan_entity_load($this->entity_type, $this->entity_id, $this->revision_id);
    $label = deploy_plan_entity_label($this->entity_type, $entity, $this->revision_id);
    return $label;
  }
}
