<?php

/**
 * @file
 * Rules implementations.
 */

/**
 * Implements hook_rules_action_info().
 */
function deploy_rules_action_info() {
  return array(
    'deploy_rules_action_deploy_plan' => array(
      'label' => t('Deploy a plan'),
      'group' => t('Deploy'),
      'parameter' => array(
        'plan_name' => array(
          'type' => 'text',
          'label' => t('Plan name'),
          'options list' => 'deploy_rules_plan_get_options'
        ),
      ),
    ),
    'deploy_rules_action_add_to_managed_plan' => array(
      'label' => t('Add entity to managed deployment plan'),
      'group' => t('Deploy'),
      'parameter' => array(
        'plan_name' => array(
          'type' => 'text',
          'label' => t('Plan name'),
          'description' => t('The plan to add the entity to.'),
          'options list' => 'deploy_rules_manager_plan_get_options'
        ),
        'entity' => array(
          'type' => 'entity',
          'label' => t('Entity'),
          'description' => t('The entity that shall be added to the plan.'),
          'wrapped' => TRUE,
        ),
      ),
    ),
    'deploy_manager_action_delete_from_plan' => array(
      'label' => t('Remove an entity from a deployment plan'),
      'group' => t('Deploy'),
      'parameter' => array(
        'plan_name' => array(
          'type' => 'text',
          'label' => t('Plan name'),
          'description' => t('The plan to remove the entity from.'),
          'options list' => 'deploy_rules_manager_plan_get_options'
        ),
        'entity' => array(
          'type' => 'entity',
          'label' => t('Entity'),
          'description' => t('The entity that shall be removed from the plan.'),
          'wrapped' => TRUE,
        ),
      ),
    ),
    'deploy_manager_entity_remove' => array(
      'label' => t('Remove Entity From Plan'),
      'group' => t('Deploy'),
      'parameter' => array(
        'dme' => array(
          'type' => 'deploy_manager_entities',
          'label' => t('Entity to remove'),
        ),
      ),
      'module' => 'deploy',
    ),
    'deploy_manager_entity_use_latest_revision' => array(
      'label' => t('Use Latest Revision of Entity'),
      'group' => t('Deploy'),
      'parameter' => array(
        'dme' => array(
          'type' => 'deploy_manager_entities',
          'label' => t('Plan Entity to update'),
        ),
      ),
      'module' => 'deploy',
    ),
  );
}

/**
 * Option callback.
 */
function deploy_rules_plan_get_options() {
  return deploy_plan_get_options(array('fetch_only' => FALSE));
}

/**
 * Option callback.
 */
function deploy_rules_manager_plan_get_options() {
  return deploy_manager_plan_get_options(array('fetch_only' => FALSE));
}

/**
 * Action callback for the "Deploy a plan" action.
 */
function deploy_rules_action_deploy_plan($plan_name) {
  if ($plan = deploy_manager_plan_load($plan_name)) {
    $plan->deploy();
    return;
  }
  watchdog('deploy', 'Cannot deploy non-managed plan %name', array('%name' => $plan_name), WATCHDOG_ERROR);
}

/**
 * Action callback for the "Add to deploy plan" action.
 */
function deploy_rules_action_add_to_managed_plan($plan_name, $entity_wrapper) {
  if (!deploy_manager_plan_load($plan_name)) {
    watchdog('deploy', 'Cannot add to non-managed plan %name', array('%name' => $plan_name), WATCHDOG_ERROR);
    return;
  }
  $entity_type = $entity_wrapper->type();
  $entity = $entity_wrapper->value();
  deploy_manager_add_to_plan($plan_name, $entity_type, $entity);
}

/**
 * Action callback for the "Remove from deploy plan" action.
 */
function deploy_manager_action_delete_from_plan($plan_name, $entity_wrapper) {
  if (!deploy_manager_plan_load($plan_name)) {
    watchdog('deploy manager', 'Attempted to remove from a non-existent deploy plan or one that cannot be arbitrarily removed from: @plan', array('@plan' => $plan_name), WATCHDOG_ERROR);
    return;
  }
  $entity_type = $entity_wrapper->type();
  $entity = $entity_wrapper->value();
  deploy_manager_delete_from_plan($plan_name, $entity_type, $entity);
}


/**
 * Action callback for the "Delete Entity From Plan" action.
 *
 * @param object $dme
 *   The deploy manager entity to be deleted.
 */
function deploy_manager_entity_remove($dme) {
  $label = $dme->label();
  $dme->delete();
  drupal_set_message(t('Entity has been removed: @label', ['@label' => $label]));
}

/**
 * Action callback for the "Use Latest Revision of Entity" action.
 */
function deploy_manager_entity_use_latest_revision($dme) {

  // We can't do anything about an entity without a revision.
  if (!$dme->revision_id) {
    return;
  }

  $entity = entity_load_single($dme->entity_type, $dme->entity_id);
  list($id, $vid, $bundle) = entity_extract_ids($dme->entity_type, $entity);
  if ($dme->revision_id == $vid) {
    // Already at the latest revision, nothing for us to do.
    $label = entity_label('deploy_manager_entities', $dme);
    drupal_set_message(t('@label already at latest revision', ['@label' => $label]), 'warning');
    return;
  }

  $label = entity_label($dme->entity_type, $entity);
  $plans = deploy_find_entity($dme->entity_type, $id, $vid);
  if (isset($plans[$dme->plan_name]) && $plans[$dme->plan_name] == $vid) {
    $dme->delete();
    drupal_set_message(t('@label [@rev_id] already exists. Removing old version.', ['@label' => $label, '@rev_id' => $vid]));
    return;
  }

  $dme->revision_id = $vid;
  $dme->save();
  drupal_set_message(t('Updated @label to @rev_id', ['@label' => $label, '@rev_id' => $vid]));
}
