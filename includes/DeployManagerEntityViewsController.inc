<?php

/**
 * @file
 * Views controller for deploy manager entities.
 */

/**
 * Views controller class for deploy manager entities.
 */
class DeployManagerEntityViewsController extends EntityDefaultViewsController {

  /**
   * Edit or add extra fields to views_data().
   */
  public function views_data() {
    $data = parent::views_data();

    $data['deploy_manager_entities'] += [
      // Custom fields
      'entity_label' => [
        'title' => t('Entity Label'),
        'help' => t('The label of the entity.'),
        'field' => [
          'handler' => 'deploy_views_handler_entity_label_field',
        ],
      ],
      'entity_link' => [
        'title' => t('Entity Link'),
        'help' => t('The link to the entity.'),
        'field' => [
          'handler' => 'deploy_views_handler_entity_link_field',
        ],
      ],
      'entity_type_label' => [
        'title' => t('Entity Type Label'),
        'help' => t('The label for the entity type.'),
        'field' => [
          'handler' => 'deploy_views_handler_entity_type_label_field',
        ],
      ],
      'related_plans' => [
        'title' => t('Related Plans'),
        'help' => t('Other plans that contain this entity.'),
        'field' => [
          'handler' => 'deploy_views_handler_related_plans_field',
        ],
      ],
      'revision_class' => [
        'title' => t('Revision Class'),
        'help' => t('CSS class name based on revision status.'),
        'field' => [
          'handler' => 'deploy_views_handler_revision_class_field',
        ],
      ],
      'revision_status' => [
        'title' => t('Revision Status'),
        'help' => t('Is the current revision the latest?'),
        'field' => [
          'handler' => 'deploy_views_handler_revision_status_field',
        ],
      ],
    ];


    return $data;
  }
}

