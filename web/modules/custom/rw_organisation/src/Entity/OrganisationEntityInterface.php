<?php

namespace Drupal\rw_organisation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Organisation entities.
 *
 * @ingroup rw_organisation
 */
interface OrganisationEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Organisation name.
   *
   * @return string
   *   Name of the Organisation.
   */
  public function getName();

  /**
   * Sets the Organisation name.
   *
   * @param string $name
   *   The Organisation name.
   *
   * @return \Drupal\rw_organisation\Entity\OrganisationEntityInterface
   *   The called Organisation entity.
   */
  public function setName($name);

  /**
   * Gets the Organisation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Organisation.
   */
  public function getCreatedTime();

  /**
   * Sets the Organisation creation timestamp.
   *
   * @param int $timestamp
   *   The Organisation creation timestamp.
   *
   * @return \Drupal\rw_organisation\Entity\OrganisationEntityInterface
   *   The called Organisation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Organisation published status indicator.
   *
   * Unpublished Organisation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Organisation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Organisation.
   *
   * @param bool $published
   *   TRUE to set this Organisation to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\rw_organisation\Entity\OrganisationEntityInterface
   *   The called Organisation entity.
   */
  public function setPublished($published);

  /**
   * Gets the Organisation revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Organisation revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\rw_organisation\Entity\OrganisationEntityInterface
   *   The called Organisation entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Organisation revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Organisation revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\rw_organisation\Entity\OrganisationEntityInterface
   *   The called Organisation entity.
   */
  public function setRevisionUserId($uid);

}
