<?php

namespace Drupal\liiweb\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\liiweb\LiiWebUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUriConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  /**
   * @param \Drupal\liiweb\LiiWebUtils $liiWebUtils
   */
  public function __construct(LiiWebUtils $liiWebUtils) {
    $this->liiWebUtils = $liiWebUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('liiweb.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($node, Constraint $constraint) {
    /** @var \Drupal\node\Entity\Node $node */
    if ($node->bundle() !== 'legislation') {
      return;
    }

    /** @var \Drupal\node\Entity\Node $existingRevision */
    $existingRevision = $this->liiWebUtils->getRevisionFromFrbrUri($node->field_frbr_uri->value);
    if (empty($existingRevision) || $existingRevision->getRevisionId() == $node->getRevisionId()) {
      return;
    }

    /** @var \Drupal\liiweb\Plugin\Validation\Constraint\UniqueUriConstraint $constraint */
    $this->context->addViolation($constraint->notUnique);
  }

}