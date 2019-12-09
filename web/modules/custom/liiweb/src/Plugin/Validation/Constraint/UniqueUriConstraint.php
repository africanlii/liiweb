<?php

namespace Drupal\liiweb\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that there are no 2 revisions with the same FRBR URI.
 *
 * @Constraint(
 *   id = "UniqueURI",
 *   label = @Translation("Unique FRBR URI", context = "Validation"),
 * )
 */
class UniqueUriConstraint extends Constraint {

  public $notUnique = 'A work with the same FRBR URI already exists.';

}
