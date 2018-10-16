<?php
/**
 * @file
 * deploy_adhoc_plan.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function deploy_adhoc_plan_default_rules_configuration() {
  $items = array();
  $items['rules_deploy_adhoc_plan_move_entity_to_adhoc_deployment_plan'] = entity_import('rules_config', '{ "rules_deploy_adhoc_plan_move_entity_to_adhoc_deployment_plan" : {
      "LABEL" : "Move entity to Ad hoc deployment plan",
      "PLUGIN" : "action set",
      "OWNER" : "rules",
      "TAGS" : [ "deploy" ],
      "REQUIRES" : [ "deploy_adhoc_plan" ],
      "ACCESS_EXPOSED" : "1",
      "USES VARIABLES" : { "entity" : { "label" : "Entity", "type" : "deploy_manager_entities" } },
      "ACTION SET" : [
        { "deploy_adhoc_plan_action_move_to_adhoc" : { "entity" : [ "entity" ] } }
      ]
    }
  }');
  return $items;
}
