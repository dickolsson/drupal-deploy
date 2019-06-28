<?php

/**
 * @file
 * Deploy UI for managing deployment plans.
 */

// @ignore style_class_names:file

/**
 * CTools Export UI class for deployment plans.
 */
class deploy_ui_plan extends ctools_export_ui {
  /**
   * Implements CTools psuedo hook_menu_alter().
   */
  public function hook_menu(&$items) {
    parent::hook_menu($items);

    // Hack around some of the limitations of the CTools Exportable UI default implementation.
    $items['admin/structure/deploy'] = $items['admin/structure/deploy/plans'];
    $items['admin/structure/deploy']['description'] = 'Manage deployment plans, endpoints etc.';
    $items['admin/structure/deploy']['title'] = 'Deploy';
    $items['admin/structure/deploy']['type'] = MENU_NORMAL_ITEM;

    $items['admin/structure/deploy/plans']['type'] = MENU_DEFAULT_LOCAL_TASK;
    $items['admin/structure/deploy/plans']['weight'] = -10;

    $items['admin/structure/deploy/plans/list/%ctools_export_ui/edit'] = $items['admin/structure/deploy/plans/list/%ctools_export_ui'];
    $items['admin/structure/deploy/plans/list/%ctools_export_ui/edit']['title'] = 'Edit';
    $items['admin/structure/deploy/plans/list/%ctools_export_ui/edit']['type'] = MENU_LOCAL_TASK;
    $items['admin/structure/deploy/plans/list/%ctools_export_ui/edit']['weight'] = 0;

    $items['admin/structure/deploy/plans/list/%ctools_export_ui']['title'] = 'View';
    $items['admin/structure/deploy/plans/list/%ctools_export_ui']['page arguments'][1] = 'view';
    $items['admin/structure/deploy/plans/list/%ctools_export_ui']['access arguments'][1] = 'view';

    $items['admin/structure/deploy/plans/list/%ctools_export_ui/view']['type'] = MENU_DEFAULT_LOCAL_TASK;

    /*
    We need to sort the menu items to ensure the parent exists before the
    children are added. Without the menu is broken.
     */
    ksort($items);

  }

  /**
   * {@inheritdoc}
   */
  public function access($op, $item) {
    return deploy_access($op);
  }

  /**
   * Form callback for basic config.
   */
  public function edit_form(&$form, &$form_state) {
    $item = $form_state['item'];

    // Basics.
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $item->title,
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine-readable name'),
      '#default_value' => $item->name,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => 'deploy_plan_load',
        'source' => array('title'),
      ),
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $item->description,
    );
    $form['plan_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Deploy plan settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['plan_settings']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug mode'),
      '#description' => t('Check this to enable debug mode with extended watchdog logging.'),
      '#default_value' => $item->debug,
    );

    // Aggregators.
    $aggregators = deploy_get_aggregator_plugins();
    $options = array();
    foreach ($aggregators as $key => $aggregator) {
      $options[$key] = array(
        'name' => $aggregator['name'],
        'description' => $aggregator['description'],
      );
    }
    $form['plan_settings']['aggregator_plugin'] = array(
      '#prefix' => '<label>' . t('Aggregator') . '</label>',
      '#type' => 'tableselect',
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => !empty($item->aggregator_plugin) ? $item->aggregator_plugin : array_keys($options)[0],
    );

    // Fetch options.
    $form['plan_settings']['fetch_only'] = array(
      '#title' => t('Fetch only'),
      '#description' => t("Select this if the content of this plan is intended to be <em>fetch-only</em> by any type of event or endpoint. This means that the plan wont have a processor or defined endpoint."),
      '#type' => 'checkbox',
      '#default_value' => $item->fetch_only,
    );

    $form['plan_settings']['deployment_process'] = array(
      '#type' => 'fieldset',
      '#title' => t('Deployment process'),
      '#description' => t('Configure how the deployment process should behave.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="fetch_only"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Processors.
    $processors = deploy_get_processor_plugins();
    $options = array();
    foreach ($processors as $key => $processor) {
      $options[$key] = array(
        'name' => $processor['name'],
        'description' => $processor['description'],
      );
    }

    $form['plan_settings']['deployment_process']['processor_plugin'] = array(
      '#prefix' => '<label>' . t('Processor') . '</label>',
      '#type' => 'tableselect',
      '#required' => FALSE,
      '#multiple' => FALSE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => !empty($item->processor_plugin) ? $item->processor_plugin : array_keys($options)[0],
    );

    // Endpoints.
    $endpoints = deploy_endpoint_load_all();
    $options = array();
    foreach ($endpoints as $endpoint) {
      $options[$endpoint->name] = array(
        'name' => check_plain($endpoint->title),
        'description' => check_plain($endpoint->description),
      );
    }
    if (!is_array($item->endpoints)) {
      $item->endpoints = unserialize($item->endpoints);
    }
    $form['plan_settings']['deployment_process']['endpoints'] = array(
      '#prefix' => '<label>' . t('Endpoints') . '</label>',
      '#type' => 'tableselect',
      '#required' => FALSE,
      '#empty' => t('No endpoints exists at the moment. <a href="!url">Go and create one</a>.', array('!url' => url('admin/structure/deploy/endpoints'))),
      '#multiple' => TRUE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => (array) $item->endpoints,
    );

    // Dependency plugin.
    $form['plan_settings']['dependency_iterator'] = array(
      '#type' => 'fieldset',
      '#title' => t('Dependency plugin'),
      '#description' => t('The iterator to handle the dependencies of the deployment plan'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['plan_settings']['dependency_iterator']['dependency_plugin'] = array(
      '#type' => 'select',
      '#title' => t('Plugin'),
      '#options' => entity_dependency_get_all_ctools_plugins(),
      '#default_value' => $item->dependency_plugin,
      '#required' => TRUE,
    );
  }

  /**
   * Submit callback for basic config.
   */
  public function edit_form_submit(&$form, &$form_state) {
    $item = $form_state['item'];

    $item->name = $form_state['values']['name'];
    $item->title = $form_state['values']['title'];
    $item->description = $form_state['values']['description'];
    $item->debug = $form_state['values']['debug'];
    $item->aggregator_plugin = $form_state['values']['aggregator_plugin'];
    $item->fetch_only = $form_state['values']['fetch_only'];

    // Processor plugin.
    if (!empty($form_state['values']['processor_plugin']) && !$form_state['values']['fetch_only']) {
      $item->processor_plugin = $form_state['values']['processor_plugin'];
    }
    else {
      $item->processor_plugin = '';
    }

    // Endpoint.
    if (!empty($form_state['values']['endpoints']) && empty($item->fetch_only)) {
      $item->endpoints = $form_state['values']['endpoints'];
    }
    else {
      $item->endpoints = array();
    }

    // Dependency Iterator.
    $item->dependency_plugin = $form_state['values']['dependency_plugin'];
  }

  /**
   * Form form configuring the aggegator.
   */
  public function edit_form_aggregator(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!is_array($item->aggregator_config)) {
      $item->aggregator_config = unserialize($item->aggregator_config);
    }

    // Create the aggregator object.
    $aggregator = new $item->aggregator_plugin(NULL, (array) $item->aggregator_config);

    $form['aggregator_config'] = $aggregator->configForm($form_state);
    if (!empty($form['aggregator_config'])) {
      $form['aggregator_config']['#tree'] = TRUE;
    }
    else {
      $form['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('There are no settings for this aggregator plugin.') . '</p>',
      );
    }
  }

  /**
   * Submit handler for the aggregator configuration form.
   */
  public function edit_form_aggregator_submit(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!empty($form_state['values']['aggregator_config'])) {
      $item->aggregator_config = $form_state['values']['aggregator_config'];
    }
    else {
      $item->aggregator_config = array();
    }
  }

  /**
   * Processor configuration form.
   */
  public function edit_form_processor(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!empty($item->processor_plugin)) {
      if (!is_array($item->processor_config)) {
        $item->processor_config = unserialize($item->processor_config);
      }

      // Create the aggregator object which is a dependency of the processor object.
      $aggregator = new $item->aggregator_plugin(NULL, (array) $item->aggregator_config);
      // Create the processor object.
      $processor = new $item->processor_plugin($aggregator, (array) $item->processor_config);

      $form['processor_config'] = $processor->configForm($form_state);
      if (!empty($form['processor_config'])) {
        $form['processor_config']['#tree'] = TRUE;
      }
    }
    if (empty($item->processor_plugin) || empty($form['processor_config'])) {
      $form['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t("No processor plugin is selected, or the selected processor plugin doesn't have any settings.") . '</p>',
      );
    }
  }

  /**
   * Submit handler for the processor configuration form.
   */
  public function edit_form_processor_submit(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!empty($form_state['values']['processor_config'])) {
      $item->processor_config = $form_state['values']['processor_config'];
    }
    else {
      $item->processor_config = array();
    }
  }

  /**
   * Renders the view deployment plan page.
   */
  public function view_page($js, $input, $plan) {
    drupal_set_title(t('Plan: @plan', array('@plan' => $plan->name)), PASS_THROUGH);

    $status = deploy_plan_get_status($plan->name);
    $status_info = deploy_status_info($status);
    if ($status_info) {
      drupal_set_message(t($status_info['keyed message'], ['%key' => $plan->name]), $status_info['class'], FALSE);
    }

    // For managed entity plans we use a view to provide additional
    // functionality.
    if ('DeployAggregatorManaged' == $plan->aggregator_plugin
      && module_exists('views_bulk_operations')
      && views_get_view('deploy_managed_entities')) {

      return views_embed_view('deploy_managed_entities', 'list_block');
    }

    $info = [];
    // Get the entity keys from the aggregator.
    $entity_keys = $plan->getEntities();
    foreach ($entity_keys as $entity_key) {
      // Get the entity info and all entities of this type.
      $entity_info = entity_get_info($entity_key['type']);
      $entity = deploy_plan_entity_load($entity_key['type'], $entity_key['id'], $entity_key['revision_id']);
      $label = deploy_plan_entity_label($entity_key['type'], $entity, $entity_key['revision_id']);

      // Some entities fail fatally with entity_uri() and
      // entity_extract_ids(). So handle this gracefully.
      try {
        $uri = entity_uri($entity_key['type'], $entity);
        if ($uri) {
          $label = l($label, $uri['path'], $uri['options']);
        }
      }
      catch (Exception $e) {
        watchdog_exception('deploy_ui', $e);
      }

      // Construct a usable array for the theme function.
      $info[] = array(
        'title' => $label,
        'type' => $entity_info['label'],
      );
    }

    $data = [
      'content' => theme('deploy_ui_overview_plan_content', array('info' => $info)),
      'plan_description' => check_plain($plan->description),
      'label_description' => t('Plan description'),
    ];

    return theme('deploy_ui_plan_view', array('vars' => $data));
  }

  /**
   * Renders the deployment plan page.
   */
  public function deploy_page($js, $input, $plan) {
    $form_state = array(
      'plugin' => $this->plugin,
      'object' => &$this,
      'ajax' => $js,
      'plan' => $plan,
      'rerender' => TRUE,
      'no_redirect' => TRUE,
    );

    $output = drupal_build_form('deploy_ui_plan_confirm_form', $form_state);

    if (!empty($form_state['executed'])) {
      try {
        $plan->deploy();
      }
      catch (Exception $e) {
        drupal_set_message(t('Something went wrong during the deployment. Check your logs or the status of the deployment for more information.'), 'error');
      }
      drupal_goto('admin/structure/deploy/plans/list/' . $plan->name);
    }

    return $output;
  }
  
}

/**
 * Form for confirming the user wants to deploy this plan.
 */
function deploy_ui_plan_confirm_form($form, $form_state) {
  $plan = $form_state['plan'];
  $path = "admin/structure/deploy/plans/list/{$plan->name}";
  if (!empty($_REQUEST['cancel_path']) && !url_is_external($_REQUEST['cancel_path'])) {
    $path = $_REQUEST['cancel_path'];
  }

  if (empty($plan->processor) || $plan->fetch_only) {
    drupal_set_message(t("The plan @name can't be deployed in push fashion because it missing a processor plugin or is configured <em>fetch-only</em>.", array('@name' => $plan->name)), 'error');
    drupal_goto($path);
  }
  $form = array();
  $form = confirm_form($form,
    t('Are you sure you want to deploy %title?', array('%title' => $plan->name)),
    $path,
    t("Deploying a plan will push its content to all its endpoints."),
    t('Deploy'),
    t('Cancel')
  );
  return $form;
}
