<?php

namespace Drupal\deploy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;

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
    $header['replicated'] = $this->t('Deployed');
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
    $row['replicated'] = $entity->get('replicated')->value ? $this->t('&#10004;') : $this->t('&#10006;');
    $row['name'] = $entity->link();
    $row['source'] = $entity->get('source')->entity ? $entity->get('source')->entity->label() : $this->t('<em>Archived</em>');
    $row['target'] = $entity->get('target')->entity ? $entity->get('target')->entity->label() : $this->t('<em>Archived</em>');
    $row['changed'] = $formatter->format($entity->getChangedTime());
    $row['created'] = $formatter->format($entity->getCreatedTime());
    return $row;
  }

}
