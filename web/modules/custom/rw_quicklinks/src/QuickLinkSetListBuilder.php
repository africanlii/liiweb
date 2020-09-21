<?php

namespace Drupal\rw_quicklinks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Quicklink set entities.
 *
 * @ingroup rw_quicklinks
 */
class QuickLinkSetListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Quicklink set ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rw_quicklinks\Entity\QuickLinkSet */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.quick_link_set.edit_form',
      ['quick_link_set' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
