<?php

class deploy_ui_plan extends ctools_export_ui {

  /**
   * Pseudo implementation of hook_menu_alter().
   *
   * @todo
   *   Can we do this in $plugin instead?
   */
  function hook_menu(&$items) {
    parent::hook_menu($items);
    $items['admin/structure/deploy/plans']['type'] = MENU_LOCAL_TASK;
    $items['admin/structure/deploy/plans']['weight'] = -10;
  }

  /**
   * Form callback for basic config.
   */
  function edit_form(&$form, &$form_state) {
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

    // Aggregators.
    $aggregators = deploy_get_aggregator_plugins();
    $options = array();
    foreach ($aggregators as $key => $aggregator) {
      $options[$key] = array(
        'name' => $aggregator['name'],
        'description' => $aggregator['description'],
      );
    }
    $form['aggregator_plugin'] = array(
      '#prefix' => '<label>' . t('Aggregator') . '</label>',
      '#type' => 'tableselect',
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => $item->aggregator_plugin,
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
    $form['processor_plugin'] = array(
      '#prefix' => '<label>' . t('Processor') . '</label>',
      '#type' => 'tableselect',
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => $item->processor_plugin,
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
    $form['endpoints'] = array(
      '#prefix' => '<label>' . t('Endpoints') . '</label>',
      '#type' => 'tableselect',
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#header' => array(
        'name' => t('Name'),
        'description' => t('Description'),
      ),
      '#options' => $options,
      '#default_value' => (array)$item->endpoints,
    );
  }

  /**
   * Submit callback for basic config.
   */
  function edit_form_submit(&$form, &$form_state) {
    $item = $form_state['item'];

    $item->name = $form_state['values']['name'];
    $item->title = $form_state['values']['title'];
    $item->description = $form_state['values']['description'];
    $item->aggregator_plugin = $form_state['values']['aggregator_plugin'];
    $item->processor_plugin = $form_state['values']['processor_plugin'];
    if (!empty($form_state['values']['endpoints'])) {
      $item->endpoints = $form_state['values']['endpoints'];
    }
    else {
      $item->endpoints = array();
    }
  }

  function edit_form_aggregator(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!is_array($item->aggregator_config)) {
      $item->aggregator_config = unserialize($item->aggregator_config);
    }

    // Create the aggregator object.
    $aggregator = new $item->aggregator_plugin((array)$item->aggregator_config);

    $form['aggregator_config'] = $aggregator->configForm($form_state);
    if (!empty($form['aggregator_config'])) {
      $form['aggregator_config']['#tree'] = TRUE;
    }
    else {
      $form['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('There are no settings for this aggregator plugin.') . '</p>'
      );
    }
  }

  function edit_form_aggregator_submit(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!empty($form_state['values']['aggregator_config'])) {
      $item->aggregator_config = $form_state['values']['aggregator_config'];
    }
    else {
      $item->aggregator_config = array();
    }
  }

  function edit_form_processor(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!is_array($item->processor_config)) {
      $item->processor_config = unserialize($item->processor_config);
    }

    // Create the aggregator object which is a dependency of the processor object.
    $aggregator = new $item->aggregator_plugin((array)$item->aggregator_config);
    // Create the processor object.
    $processor = new $item->processor_plugin($aggregator, (array)$item->processor_config);

    $form['processor_config'] = $processor->configForm($form_state);
    if (!empty($form['config']['processor_config'])) {
      $form['processor_config']['#tree'] = TRUE;
    }
    else {
      $form['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('There are no settings for this processor plugin.') . '</p>'
      );
    }
  }

  function edit_form_processor_submit(&$form, &$form_state) {
    $item = $form_state['item'];
    if (!empty($form_state['values']['processor_config'])) {
      $item->processor_config = $form_state['values']['processor_config'];
    }
    else {
      $item->processor_config = array();
    }
  }

  function deploy_page($js, $input, $item) {
    $form_state = array(
      'plugin' => $this->plugin,
      'object' => &$this,
      'ajax' => $js,
      'item' => $item,
      'rerender' => TRUE,
      'no_redirect' => TRUE,
    );

    $output = drupal_build_form('deploy_ui_plan_confirm_form', $form_state);

    if (!empty($form_state['executed'])) {
      $item->deploy();
      $export_key = $this->plugin['export']['key'];
      $message = str_replace('%title', check_plain($item->{$export_key}), '%title has been deployed');
      drupal_set_message($message);
      drupal_goto(ctools_export_ui_plugin_base_path($this->plugin));
    }

    return $output;
  }

}

function deploy_ui_plan_confirm_form($form, $form_state) {
  $plugin = $form_state['plugin'];
  $item = $form_state['item'];

  $form = array();

  $export_key = $plugin['export']['key'];
  $path = empty($_REQUEST['cancel_path']) ? ctools_export_ui_plugin_base_path($plugin) : $_REQUEST['cancel_path'];

  $form = confirm_form($form,
    t('Are you sure you want to deploy %title?', array('%title' => $item->{$export_key})),
    $path,
    t("Deploying a plan will push it's content to all it's endpoints."),
    t('Deploy'),
    t('Cancel')
  );
  return $form;
}
