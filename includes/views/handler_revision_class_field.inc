<?php

/**
 * @file
 * Deploy views revision status class handler.
 */

/**
 * Class for handling the deploy manager entity revision class "field".
 */
class deploy_views_handler_revision_class_field extends deploy_views_handler_base {

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    $entity = $this->entity_load($values);
    $css_class = '';
    if (!deploy_is_latest_revision($values->entity_type, $entity)) {
      drupal_set_message(t('A newer revision exists for one or more items in this plan.'), 'warning', FALSE);
      $css_class = 'newer-available';
    }
    return $css_class;
  }
}
