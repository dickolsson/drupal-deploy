<?php
/**
 * @file
 * Deploy UI page functions.
 */

/**
 * Theme callback for a plan's content.
 */
function theme_deploy_ui_overview_plan_content($variables) {
  $info = $variables['info'];

  if (empty($info)) {
    return t('No content in this plan.');
  }

  $header = [t('Title'), t('Type')];
  $table = [
    'header' => $header,
    'rows' => [],
  ];

  foreach ($info as $values) {
    $table['rows'][] = [$values['title'], $values['type']];
  }

  return theme('table', $table);
}

/**
 * Form constructor for the empty plan page.
 */
function deploy_ui_empty_plan_form($form, &$form_state, $plan) {
  $form = array();

  $form['deploy_plan'] = array(
    '#type' => 'value',
    '#value' => $plan,
  );

  return confirm_form(
    $form,
    t('Are you sure you want to empty the @plan plan?', array('@plan' => $plan->title)),
    'admin/structure/deploy/plans/list/' . $plan->name,
    t('Emptying the plan will remove all items from the plan.'),
    t('Empty'),
    t('Cancel')
  );
}

/**
 * Submit handler for empty plan form.
 */
function deploy_ui_empty_plan_form_submit($form, &$form_state) {
  $plan = $form_state['values']['deploy_plan'];
  $plan->emptyPlan();

  drupal_set_message(
    t('All items were removed from the @plan plan.', array('@plan' => $plan->title))
  );

  $form_state['redirect'] = 'admin/structure/deploy/plans/list/' . $plan->name;
}

/**
 * Callback for the flatten plan page.
 */
function deploy_ui_flatten_plan_form($form, &$form_state, $plan) {
  $form = array();

  $form['deploy_plan'] = array(
    '#type' => 'value',
    '#value' => $plan,
  );

  return confirm_form(
    $form,
    t('Are you sure you want to remove all duplicate content from the @plan plan?', array('@plan' => $plan->title)),
    'admin/structure/deploy/plans/list/' . $plan->name . '/view',
    t('This operation will remove all older revisions from a plan so only the latest versions remain. This operation can not be undone.'),
    t('Remove Duplicates'),
    t('Cancel')
  );
}

/**
 * Submit handler for flatten plan form.
 */
function deploy_ui_flatten_plan_form_submit($form, &$form_state) {
  $plan = $form_state['values']['deploy_plan'];
  $plan->flatten();

  drupal_set_message(
    t('Duplicate content has been removed from the @plan plan.', array('@plan' => $plan->title))
  );

  $form_state['redirect'] = 'admin/structure/deploy/plans/list/' . $plan->name . '/view';
}

/**
 * Callback for the flatten plan page.
 */
function deploy_ui_manage_plan_form($form, &$form_state, $plan) {
  $form = array();

  $form['deploy_plan'] = array(
    '#type' => 'value',
    '#value' => $plan,
  );

  $base_options = [
    -1 => t('Remove'),
    -9999 => t('Keep'),
    ];

  // Get the entity keys from the aggregator.
  $entity_keys = $plan->getEntities();
  foreach ($entity_keys as $entity_key) {
    // Get the entity info and all entities of this type.
    $entity_info = entity_get_info($entity_key['type']);

    if (!empty($entity_info['entity keys']['revision']) && !empty($entity_key['revision_id'])) {
      $entity = entity_revision_load($entity_key['type'], $entity_key['revision_id']);
    }
    else {
      $entity = entity_load_single($entity_key['type'], $entity_key['id']);
    }

    $title = "{$entity_key['type']}:{$entity_key['id']}";
    $label = entity_label($entity_key['type'], $entity);
    if ($label) {
      $title = $label;
    }

    $options = $base_options;
    if ($entity_info['entity keys']['revision']) {

      if (!empty($entity_key['revision_id'])) {
        $title = t('@title (rev:@rev_id)', array('@title' => $title, '@rev_id' => $entity_key['revision_id']));
      }

      $revisions = db_select($entity_info['revision table'], 'tbl')
        ->fields('tbl', [$entity_info['entity keys']['revision']])
        ->condition($entity_info['entity keys']['id'], $entity_key['id'])
        ->condition($entity_info['entity keys']['revision'], $entity_key['revision_id'], '<>')
        ->execute()
        ->fetchCol();

      $options += array_combine($revisions, $revisions);
    }

    $element_key = "{$entity_key['type']}:{$entity_key['id']}:{$entity_key['revision_id']}";
    $form[$element_key] = [
      '#type' => 'select',
      '#title' => $title,
      '#options' => $options,
      '#default_value' => -9999,
    ];
  }

  $form['submit'] = [
    '#type' => 'submit',
    '#value' => 'Update',
  ];

  return $form;
}
