<?php

/**
 * @file
 * Confirm form and Submit Handlers functions.
 */

module_load_include('inc', 'deploy_remote_cc', 'plugins/DeployRemoteCcServiceRestJSON');
module_load_include('inc', 'deploy_remote_cc', 'plugins/DeployRemoteCcAuthenticatorSession');

/**
 * Confirm form.
 */
function deploy_remote_cc_form($form, &$form_state, $plan) {
  $form = array();

  $form['deploy_plan'] = array(
    '#type' => 'value',
    '#value' => $plan,
  );

  return confirm_form(
    $form,
    t('Are you sure you want to clear all the caches?'),
    'admin/structure/deploy/plans/list/' . $plan->name . '/view',
    t('This operation will clear all the remote caches.'),
    t('Clear Remote Caches'),
    t('Cancel')
  );
}

/**
 * Submit handler for clearing caches form.
 */
function deploy_remote_cc_form_submit($form, &$form_state) {
  global $user;
  $plan = $form_state['values']['deploy_plan'];
  $configured_endpoint = variable_get('deploy_remote_cc_endpoint');
  $endpoint = deploy_endpoint_load($configured_endpoint);
  $login_status = $token = '';

  if (isset($endpoint) && !empty($endpoint)) {
    try {
      // Attempt to get the information from the remote environment.
      $endpoint->service = new DeployRemoteCcServiceRestJSON($endpoint->service_config);
      $authenticator = new DeployRemoteCcAuthenticatorSession($endpoint->service, $endpoint->authenticator_config);
      list($login_status, $token) = $authenticator->logIn();
    }
    catch (DeployAuthenticationException $e) {
      // Catch it if we can't access the end environment.
      watchdog('deploy_remote_cc', 'Unable to login to remote environment.', array(), WATCHDOG_ERROR);
    }

    try {
      $data = drupal_json_encode(array('username' => $user->name));
      // Call the cache clear api.
      $endpoint_url = deploy_endpoint_load($configured_endpoint)->service_config['url'] . '/remote_cache_clear/cache_clear';
      $cache_clear_response = $endpoint->service->httpRequest($endpoint_url, 'POST', $data);
      drupal_set_message(t("Remote endpoint's caches has been cleared."));
    }
    catch (Exception $e) {
      // Catch it if we can't access the end environment.
      watchdog('deploy_remote_cc', 'Unable to clear caches from the remote environment.', array(), WATCHDOG_ERROR);
      drupal_set_message(t('Unable to clear caches from the remote environment.'), 'error');
    }
    finally {
      // Logout in any case clearing caches is successful or not.
      $authenticator->logOut($login_status, $token);
    }
  }
  else {
    drupal_set_message(t('Endpoints not configured on which caches should be cleared. Configure at <a href="/admin/config/content/deploy_remote_cc">%link_text</a>.',
      array(
        '%link_text' => 'link',
      )
    ), 'error');
  }

  $form_state['redirect'] = 'admin/structure/deploy/plans/list/' . $plan->name . '/view';
}
