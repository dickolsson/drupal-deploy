<?php
/**
 * @file
 * Deploy UI page functions.
 */

/**
 * Theme callback for a plan's content.
 */
function theme_deploy_ui_overview_plan_content($variables) {
  drupal_add_css(drupal_get_path('module', 'deploy_ui') . '/css/deploy_ui.admin.css');
  $info = $variables['info'];

  if (empty($info)) {
    return t('No content in this plan.');
  }

  $status_latest = t('Latest');
  $header = [t('Title'), t('Type'), t('Revision Status')];
  $table = [
    'header' => $header,
    'rows' => [],
    'attributes' => [
      'class' => ['deploy-plan'],
    ]
  ];

  foreach ($info as $values) {
    $fields = [$values['title'], $values['type'], $values['status']];
    if ($values['status'] == $status_latest) {
      $table['rows'][] = $fields;
    }
    else {
      $table['rows'][] = [
        'data' => $fields,
        'class' => ['newer-available'],
      ];
    }
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
    t('Are you sure you want to flatten the @plan plan?', array('@plan' => $plan->title)),
    'admin/structure/deploy/plans/list/' . $plan->name,
    t('Flattening the plan will remove all duplicated items from the plan.'),
    t('Flatten'),
    t('Cancel')
  );
}

/**
 * Submit handler for empty plan form.
 */
function deploy_ui_flatten_plan_form_submit($form, &$form_state) {
  $plan = $form_state['values']['deploy_plan'];
  $plan->flatten();

  drupal_set_message(
    t('The @plan plan was flattened.', array('@plan' => $plan->title))
  );

  $form_state['redirect'] = 'admin/structure/deploy/plans/list/' . $plan->name;
}
