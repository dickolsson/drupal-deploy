<?php

/**
 * @file
 * Deploy Views Related Plans field handler.
 */

/**
 * Class for handling the Deploy Manager Related Plans "field",
 */
class deploy_views_handler_related_plans_field extends deploy_views_handler_base {

  /**
   * {@inheritdoc}
   */
  public function render($values) {

    $plans = [];

    $matched_plans = deploy_find_entity($values->entity_type, $values->entity_id, $values->plan_name);
    if ($matched_plans) {
      foreach ($matched_plans as $name => $rev) {
        $link_label = t('@title [rev:@rev_id]', ['@title' => $name, '@rev_id' => $rev]);
        $plans[] = l($link_label, 'admin/structure/deploy/plans/list/' . $name);
      }
      return theme('item_list', ['items' => $plans]);
    }

    return '';
  }
}
