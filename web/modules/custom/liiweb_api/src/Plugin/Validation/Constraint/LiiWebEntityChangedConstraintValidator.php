<?php

namespace Drupal\liiweb_api\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator;
use Drupal\Core\Entity\RevisionableInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the LiiWebEntityChanged constraint.
 */
class LiiWebEntityChangedConstraintValidator extends EntityChangedConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return;
    }

    parent::validate($entity, $constraint);
  }

}
