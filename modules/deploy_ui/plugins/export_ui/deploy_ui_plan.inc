<?php
/**
 * @file
 * UI plugin for Deploy UI.
 */

$plugin = array(
  'schema' => 'deploy_plans',
  'access' => 'administer deployment plans',

  'menu' => array(
    'menu prefix' => 'admin/structure/deploy',
    'menu item' => 'plans',
    'menu title' => 'Plans',
    'items' => array(
      'view' => array(
        'path' => 'list/%ctools_export_ui/view',
        'title' => 'View',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array('deploy_ui_plan', 'view', 5),
        'type' => MENU_LOCAL_TASK,
        'weight' => -99,
      ),
      'deploy' => array(
        'path' => 'list/%ctools_export_ui/deploy',
        'title' => 'Deploy',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array('deploy_ui_plan', 'deploy', 5),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array('deploy_ui_plan', 'deploy', 5),
        'type' => MENU_LOCAL_ACTION,
      ),
    ),
  ),

  'title singular' => t('plan'),
  'title singular proper' => t('Plan'),
  'title plural' => t('plans'),
  'title plural proper' => t('Plans'),

  'use wizard' => TRUE,

  'handler' => array(
    'class' => 'deploy_ui_plan',
    'parent' => 'ctools_export_ui',
  ),

  'allowed operations' => array(
    'view' => array('title' => t('View')),
    'edit' => array('title' => t('Edit')),
    'deploy' => array('title' => t('Deploy')),
  ),

  'form info' => array(
    'order' => array(
      'basic' => t('Basics'),
      'aggregator' => t('Aggregator'),
      'processor' => t('Processor'),
    ),
  ),
);
