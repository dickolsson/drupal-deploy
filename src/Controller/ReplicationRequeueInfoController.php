<?php

namespace Drupal\deploy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReplicationRequeueInfoController extends ControllerBase {

  /**
   * Title callback.
   *
   * @param int $replication_id
   *
   * @return string Array of page elements to render.
   *    Array of page elements to render.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewRequeueInfo($replication_id) {
    /** @var \Drupal\workspace\Entity\Replication $entity */
    $entity = $this->entityTypeManager()->getStorage('replication')->load($replication_id);
    $source = $target = $this->t('*' . 'Unknown' . '*');
    if (!empty($entity->source->entity)) {
      $source = $entity->source->entity->label();
    }
    if (!empty($entity->target->entity)) {
      $target = $entity->target->entity->label();
    }
    $arguments = [
      '%source' => $source,
      '%target' => $target,
    ];
    $build['#markup'] = $this->t('Deployment from %source to %target has been requeued.', $arguments);
    $build['#markup'] .= "</br></br>";
    if (!empty($entity->getReplicationFailInfo())) {
      $build['#markup'] .= $this->t('<strong>Reason: </strong>') . $entity->getReplicationFailInfo();
    }
    else {
      $build['#markup'] .= $this->t('Reason: Unknown.');
    }
    $build['#markup'] .= "</br></br>";
    $build['#markup'] .= $this->t('Please check the logs for more info.');
    return $build;
  }

  /**
   * Renders replication fail info.
   *
   * @param int $replication_id
   *
   * @return string Array of page elements to render.
   *    Array of page elements to render.
   */
  public function viewTitle($replication_id) {
    $entity = $this->entityTypeManager()->getStorage('replication')->load($replication_id);
    return 'Deployment "' . $entity->label() . '" has been requeued';
  }

}
