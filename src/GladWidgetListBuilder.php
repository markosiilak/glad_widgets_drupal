<?php

namespace Drupal\glad_widgets;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for Glad Widget entities.
 */
class GladWidgetListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Nimetus');
    $header['module'] = $this->t('Moodul');
    $header['height'] = $this->t('Kõrgus');
    $header['status'] = $this->t('Staatus');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\glad_widgets\GladWidgetInterface $entity */
    $row['label'] = $entity->label();
    $row['module'] = $entity->getModule();
    $row['height'] = $entity->getHeight();
    $row['status'] = $entity->status() ? $this->t('Aktiivne') : $this->t('Mitteaktiivne');
    return $row + parent::buildRow($entity);
  }

}
