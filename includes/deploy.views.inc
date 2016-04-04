<?php

/**
 * @file
 * Deploy module views integration functions.
 */

/**
 * Implements hook_views_data().
 */
function deploy_views_data_alter(&$data) {

}

/**
 * Implements hook_views_handlers().
 */
function deploy_views_handlers() {
  return [
    'info' => [
      'path' => drupal_get_path('module', 'deploy') . '/includes/views',
    ],
  ];
}
