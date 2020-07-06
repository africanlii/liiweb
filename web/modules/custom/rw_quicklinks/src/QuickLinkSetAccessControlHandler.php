<?php

namespace Drupal\rw_quicklinks;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Quicklink set entity.
 *
 * @see \Drupal\rw_quicklinks\Entity\QuickLinkSet.
 */
class QuickLinkSetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rw_quicklinks\Entity\QuickLinkSetInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished quicklink set entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published quicklink set entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit quicklink set entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete quicklink set entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add quicklink set entities');
  }

}
