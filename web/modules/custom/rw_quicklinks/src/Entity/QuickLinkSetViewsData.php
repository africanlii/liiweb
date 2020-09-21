<?php

namespace Drupal\rw_quicklinks\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Quicklink set entities.
 */
class QuickLinkSetViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
