<?php

namespace Drupal\deploy\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Replication entities.
 */
class ReplicationViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['replication']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Deployment'),
      'help' => $this->t('The Replication ID.'),
    ];

    return $data;
  }

}
