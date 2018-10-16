<?php

/**
 * @file
 * Deploy Remote Cache clear settings function.
 */

/**
 * Admin settings form.
 */
function deploy_remote_cc_endpoint_settings($form, $form_state) {

  $endpoints = deploy_endpoint_load_all();

  if (!count($endpoints)) {
    $msg = t('No deploy endpoints configured.');
    if (user_access('administer deployment plans')) {
      $url = url('admin/structure/deploy/endpoints');
      $msg = t('No deploy endpoints configured. <a href="!url">Add an endpoint</a>.', array('!url' => $url));
    }
    return array('text' => array('#markup' => $msg));
  }

  $form['deploy_remote_cc_endpoint'] = array(
    '#type' => 'select',
    '#title' => t('Endpoint'),
    '#description' => t('Select a deploy endpoint to clear caches on the respective server.'),
    '#empty_option' => t('- None -'),
    '#options' => drupal_map_assoc(array_keys($endpoints)),
    '#default_value' => variable_get('deploy_remote_cc_endpoint'),
    '#empty_value' => FALSE,
  );

  return system_settings_form($form);
}
