<?php

/**
 * @file
 * Deploy Views Entity Link field handler.
 */

/**
 * Class for handling the Deploy Manager Entity Link "field",
 */
class deploy_views_handler_entity_link_field extends deploy_views_handler_base {

  /**
   * {@inheritdoc}
   */
  public function render($values) {

    $entity = $this->entity_load($values);
    $uri = entity_uri($values->entity_type, $entity);
    $label = deploy_plan_entity_label($values->entity_type, $entity, $values->revision_id);

    $options = [];
    if ($uri['options']) {
      $options = $uri['options'];
    }

    $link = l($label, $uri['path'], $options);
    return $link;
  }
}
