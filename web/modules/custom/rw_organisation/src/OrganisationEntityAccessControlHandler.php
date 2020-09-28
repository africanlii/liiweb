<?php

namespace Drupal\rw_organisation;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Organisation entity.
 *
 * @see \Drupal\rw_organisation\Entity\OrganisationEntity.
 */
class OrganisationEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rw_organisation\Entity\OrganisationEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished organisation entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published organisation entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit organisation entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete organisation entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add organisation entities');
  }

}
