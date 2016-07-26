<?php

/**
 * @file
 * Default views for the deploy module.
 */

/**
 * Implements hook_views_default_views().
 */
function deploy_views_default_views() {
  $views = [];

  $view = new view();
  $view->name = 'deploy_managed_entities';
  $view->description = '';
  $view->tag = 'deploy';
  $view->base_table = 'deploy_manager_entities';
  $view->human_name = 'Deploy Managed Entities';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Deployment Plan';
  $handler->display->display_options['css_class'] = 'deploy-plan';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'view deployment plans';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['row_class'] = '[revision_class]';
  $handler->display->display_options['style_options']['columns'] = array(
    'views_bulk_operations' => 'views_bulk_operations',
    'entity_label' => 'entity_label',
    'revision_status' => 'revision_status',
    'timestamp' => 'timestamp',
    'related_plans' => 'related_plans',
    'revision_class' => 'revision_class',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'views_bulk_operations' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'entity_label' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'revision_status' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'timestamp' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'related_plans' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'revision_class' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* No results behavior: No content */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['ui_name'] = 'No content';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = 'No content in this plan.';
  $handler->display->display_options['empty']['area']['format'] = 'filtered_html';
  /* Field: Bulk operations: Deploy Manager Entity */
  $handler->display->display_options['fields']['views_bulk_operations']['id'] = 'views_bulk_operations';
  $handler->display->display_options['fields']['views_bulk_operations']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['views_bulk_operations']['field'] = 'views_bulk_operations';
  $handler->display->display_options['fields']['views_bulk_operations']['label'] = 'Actions';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['display_type'] = '0';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['enable_select_all_pages'] = 1;
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['force_single'] = 0;
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['entity_load_capacity'] = '10';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_operations'] = array(
    'rules_component::rules_remove_entity_from_deployment_plan' => array(
      'selected' => 1,
      'postpone_processing' => 0,
      'skip_confirmation' => 0,
      'override_label' => 0,
      'label' => '',
    ),
    'rules_component::rules_use_latest_revision_of_entity' => array(
      'selected' => 1,
      'postpone_processing' => 0,
      'skip_confirmation' => 0,
      'override_label' => 0,
      'label' => '',
    ),
  );
  /* Field: Deploy Manager Entity: Entity Type Label */
  $handler->display->display_options['fields']['entity_type_label']['id'] = 'entity_type_label';
  $handler->display->display_options['fields']['entity_type_label']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['entity_type_label']['field'] = 'entity_type_label';
  $handler->display->display_options['fields']['entity_type_label']['label'] = 'Type';
  /* Field: Deploy Manager Entity: Entity Link */
  $handler->display->display_options['fields']['entity_link']['id'] = 'entity_link';
  $handler->display->display_options['fields']['entity_link']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['entity_link']['field'] = 'entity_link';
  $handler->display->display_options['fields']['entity_link']['label'] = 'Entity';
  /* Field: Deploy Manager Entity: Revision Status */
  $handler->display->display_options['fields']['revision_status']['id'] = 'revision_status';
  $handler->display->display_options['fields']['revision_status']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['revision_status']['field'] = 'revision_status';
  /* Field: Deploy Manager Entity: Added */
  $handler->display->display_options['fields']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['date_format'] = 'time ago';
  $handler->display->display_options['fields']['timestamp']['second_date_format'] = 'really_short';
  /* Field: Deploy Manager Entity: Related Plans */
  $handler->display->display_options['fields']['related_plans']['id'] = 'related_plans';
  $handler->display->display_options['fields']['related_plans']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['related_plans']['field'] = 'related_plans';
  /* Field: Deploy Manager Entity: Revision Class */
  $handler->display->display_options['fields']['revision_class']['id'] = 'revision_class';
  $handler->display->display_options['fields']['revision_class']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['fields']['revision_class']['field'] = 'revision_class';
  $handler->display->display_options['fields']['revision_class']['label'] = '';
  $handler->display->display_options['fields']['revision_class']['exclude'] = TRUE;
  $handler->display->display_options['fields']['revision_class']['element_label_colon'] = FALSE;
  /* Sort criterion: Deploy Manager Entity: Added */
  $handler->display->display_options['sorts']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['sorts']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['order'] = 'DESC';
  /* Contextual filter: Deploy Manager Entity: Plan Name */
  $handler->display->display_options['arguments']['plan_name']['id'] = 'plan_name';
  $handler->display->display_options['arguments']['plan_name']['table'] = 'deploy_manager_entities';
  $handler->display->display_options['arguments']['plan_name']['field'] = 'plan_name';
  $handler->display->display_options['arguments']['plan_name']['default_action'] = 'default';
  $handler->display->display_options['arguments']['plan_name']['title_enable'] = TRUE;
  $handler->display->display_options['arguments']['plan_name']['title'] = 'Deployment Plan: %1';
  $handler->display->display_options['arguments']['plan_name']['default_argument_type'] = 'raw';
  $handler->display->display_options['arguments']['plan_name']['default_argument_options']['index'] = '5';
  $handler->display->display_options['arguments']['plan_name']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['plan_name']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['plan_name']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['plan_name']['limit'] = '0';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'list_block');

  $views[$view->name] = $view;

  return $views;
}
