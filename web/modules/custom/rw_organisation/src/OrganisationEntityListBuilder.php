<?php

namespace Drupal\rw_organisation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Organisation entities.
 *
 * @ingroup rw_organisation
 */
class OrganisationEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Organisation ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rw_organisation\Entity\OrganisationEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.organisation_entity.edit_form',
      ['organisation_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
