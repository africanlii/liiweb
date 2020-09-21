<?php

namespace Drupal\rw_quicklinks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Quicklink set entities.
 *
 * @ingroup rw_quicklinks
 */
interface QuickLinkSetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Quicklink set name.
   *
   * @return string
   *   Name of the Quicklink set.
   */
  public function getName();

  /**
   * Sets the Quicklink set name.
   *
   * @param string $name
   *   The Quicklink set name.
   *
   * @return \Drupal\rw_quicklinks\Entity\QuickLinkSetInterface
   *   The called Quicklink set entity.
   */
  public function setName($name);

  /**
   * Gets the Quicklink set creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Quicklink set.
   */
  public function getCreatedTime();

  /**
   * Sets the Quicklink set creation timestamp.
   *
   * @param int $timestamp
   *   The Quicklink set creation timestamp.
   *
   * @return \Drupal\rw_quicklinks\Entity\QuickLinkSetInterface
   *   The called Quicklink set entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Quicklink set published status indicator.
   *
   * Unpublished Quicklink set are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Quicklink set is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Quicklink set.
   *
   * @param bool $published
   *   TRUE to set this Quicklink set to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\rw_quicklinks\Entity\QuickLinkSetInterface
   *   The called Quicklink set entity.
   */
  public function setPublished($published);

}
