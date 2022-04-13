<?php

namespace Drupal\book\Plugin\Validation\Constraint;

use Drupal\book\BookManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing the book outline in pending revisions.
 */
class BookOutlineConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Creates a new BookOutlineConstraintValidator instance.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!empty($entity->book) && !$entity->isNew() && !$entity->isDefaultRevision()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $this->bookManager->loadBookLink($entity->id(), FALSE) ?: [
        'bid' => 0,
        'weight' => 0,
      ];
      if (empty($original['pid'])) {
        $original['pid'] = -1;
      }

      // Validate the book structure when the user has access to manage book
      // outlines. When the user can manage book outlines, the book variable
      // will be populated even if the node is not part of the book.
      // If the user cannot manage book outlines, the book variable will be
      // empty and we can safely ignore the constraints as the outline cannot
      // be changed by this user.
      if (!empty($entity->book)) {
        if ($entity->book['bid'] != $original['bid']) {
          $this->context->buildViolation($constraint->message)
            ->atPath('book.bid')
            ->setInvalidValue($entity)
            ->addViolation();
        }
        // We add this to remove the constraint when the node is not a true
        // book.
        if ($original['pid'] !== -1 && $entity->book['pid'] != $original['pid']) {
          $this->context->buildViolation($constraint->message)
            ->atPath('book.pid')
            ->setInvalidValue($entity)
            ->addViolation();
        }
        if ($entity->book['weight'] != $original['weight']) {
          $this->context->buildViolation($constraint->message)
            ->atPath('book.weight')
            ->setInvalidValue($entity)
            ->addViolation();
        }
      }
    }
  }

}
