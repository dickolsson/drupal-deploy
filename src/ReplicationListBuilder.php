<?php

namespace Drupal\deploy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\workspace\Entity\Replication;

/**
 * Defines a class to build a listing of Replication entities.
 *
 * @ingroup workspace
 */
class ReplicationListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['replication_status'] = $this->t('Status');
    $header['name'] = $this->t('Name');
    $header['source'] = $this->t('Source');
    $header['target'] = $this->t('Target');
    $header['changed'] = $this->t('Updated');
    $header['created'] = $this->t('Created');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $formatter = \Drupal::service('date.formatter');
    /* @var $entity \Drupal\workspace\Entity\Replication */
    $row['replication_status'] = $this->getReplicationStatusIcon($entity->get('replication_status')->value);
    $row['name'] = $entity->label();
    $row['source'] = $entity->get('source')->entity ? $entity->get('source')->entity->label() : $this->t('<em>Archived</em>');
    $row['target'] = $entity->get('target')->entity ? $entity->get('target')->entity->label() : $this->t('<em>Archived</em>');
    $row['changed'] = $formatter->format($entity->getChangedTime());
    $row['created'] = $formatter->format($entity->getCreatedTime());
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Determine when cron last ran.
    $cron_last = \Drupal::state()->get('system.cron_last');
    if (!is_numeric($cron_last)) {
      $cron_last = \Drupal::state()->get('install_time', 0);
    }

    $build = [
      '#markup' => $this->t('Last replication ran @time ago', ['@time' => \Drupal::service('date.formatter')->formatTimeDiffSince($cron_last)]),
    ];
    $build += parent::render();
    return $build;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('changed', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  protected function getReplicationStatusIcon($status) {
    $icons = [
      Replication::FAILED => $this->t('&#10006; Failed'),
      Replication::QUEUED => $this->t('&#x231A Queued'),
      Replication::REPLICATING => $this->t('In progress'),
      Replication::REPLICATED => $this->t('&#10004; Done'),
    ];
    return $icons[$status];
  }

}
