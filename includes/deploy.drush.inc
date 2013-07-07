<?php
/**
 * @file
 * Drush integration for the Deploy module.
 */

/**
 * Implementation of hook_drush_command().
 */
function deploy_drush_command() {
  // Drupal isn't bootstraped long enough, when this hook is fired, to find out
  // what authentication methods and options that are available. So we must
  // execute the full bootstrap here. Sorry for that.
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  $items = array();
  // Drush executes this hook even if the module is disabled.
  if (module_exists('deploy')) {
    // Fetch all auth types to find out what options they have.
    $auth_types = deploy_get_auth_types();
    $auth_options = array();
    foreach ($auth_types as $auth_type => $auth_info) {
      $form = deploy_auth_invoke($auth_type, 'form callback');
      foreach ($form as $option => $control) {
        $auth_options[$option] = dt("Only when server is using !title.", array('!title' => drupal_strtolower($auth_info['title']))) . ' ' . $control['#description'];
      }
    }
    $items['deploy'] = array(
      'description' => 'Executes a deployment plan',
      'options' => array (
        'plan' => 'The pid of the plan you want to deploy.',
        'server' => 'The sid of the server you want to deploy to.',
        'silent' => 'Setting this to any value will suppress the file listing on deployment.',
      ) + $auth_options,
    );
  }
  return $items;
}


/**
 * Deploy a plan from the command line with drush.
 */
function drush_deploy() {
  global $user;
  $args = func_get_args();
  $vars = array();

  // Check to see if we should list 'internal' plans as an option.
  $internal = $args[0] == 'all' ? TRUE : FALSE;
  $plans = deploy_get_plans($internal);

  // If there are plans...
  if (!empty($plans)) {
    // If a plan was not specified as an option, then prompt for it.
    foreach ($plans as $plan) {
      $plan_options[$plan['pid']] = $plan['name'];
    }
    if (!$plan_id = drush_get_option('plan')) {
      // List plans and ask which one to deploy. Save the response in $vars.
      if (!($plan_id = drush_choice($plan_options, dt('Which plan would you like to deploy?')))) {
        return;
      }
    }
    $vars['!plan'] = $plan_options[$plan_id];

    // If a server was not specified as an option, then prompt for it.
    $server_options = deploy_get_servers();
    if (!$server_id = drush_get_option('server')) {
      // List servers and ask which one to deploy to. Save the response in $vars.
      if (!$server_id = drush_choice($server_options, dt('Which server would you like to deploy to?'))) {
        return;
      }
    }
    $vars['!server'] = $server_options[$server_id];

    // Now we need to fetch the form for the auth type this server uses.
    // By doing so we know what options to promt the user for.
    $server = deploy_get_server($server_id);
    // Check if the server exists before we go further.
    if (empty($server)) {
      drush_log(dt('A server with that id could not be found.'), 'error', TRUE);
      return;
    }

    $form = deploy_auth_invoke($server['auth_type'], 'form callback');
    // This option array will work as $form_state['values'] that is normally
    // passed to deploy_plan_init().
    $options = array();
    foreach ($form as $option => $control) {
      if (!$value = drush_get_option($option)) {
        if (!$value = drush_prompt(dt($control['#title'], array('!option' => $option)))) {
          return;
        }
      }
      $options[$option] = $value;
    }

    if (!drush_confirm(dt('Deploy !plan to !server', $vars))) {
      return;
    }

    // For anyone looking to work with the Deploy API, this is a pretty
    // complete tutorial.

    // Attempt to get a session on the remote server.
    if (deploy_plan_init($plan_id, $server_id, $options)) {
      $items = deploy_get_plan_items($plan_id);
      // Abort if we have no items in the plan.
      if (empty($items)) {
        drush_log(dt('There are no items in the plan.'), 'error', TRUE);
        return;
      }
      $count = 0;

      // Run through the items in our plan and run the dependency checking
      // hooks.
      foreach ($items as $item) {
        $count++;
        deploy_plan_check_item($item['module'], $item['data']);
      }

      // If silent is not specified then report number of items to be pushed.
      if (!drush_get_option('silent')) {
        drush_log(dt('!count items checked', array('!count' => $count)), 'ok', FALSE);
      }

      // Run the post-check cleanup routines.
      module_invoke_all('deploy_check_cleanup', $plan_id);

      // The plan items can change (and almost certainly have
      // changed) as a result of plan cleanup, so get the list again.
      $items = deploy_get_plan_items($plan_id);
      $count = 0;

      // Go through this list and deploy each item one by one.
      foreach ($items as $item) {
        $count++;
        if (!drush_get_option('silent')) {
          drush_log(dt('Deploying item: !item', array('!item' => $item['description'])), 'ok', FALSE);
        }
        deploy_item($item);
      }
      $deploy_log_id = variable_get('deploy_log_id', NULL);

      // Run the post-deployment cleanup routines.
      deploy_plan_cleanup();

      // Hey look we're done.
      // @todo: make this report a link to the log details for the lazy.
      drush_log(dt('Deployment completed, check deployment log !log_id for results'), array('!log_id' => $deploy_log_id), 'ok', FALSE);
    }
    else {
      drush_log(dt('The plan could not be initiated.'), 'error', TRUE);
    }
  }
  else {
    drush_print(dt('There are no plans to deploy'));
  }
}
