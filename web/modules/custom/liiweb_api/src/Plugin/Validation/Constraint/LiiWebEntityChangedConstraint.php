<?php

namespace Drupal\liiweb_api\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @Constraint(
 *   id = "LiiWebEntityChanged",
 *   label = @Translation("Entity changed (ignoring non default revisions)", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class LiiWebEntityChangedConstraint extends EntityChangedConstraint {

}
