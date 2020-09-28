<?php

namespace Drupal\liiweb_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'AdvancedSearchBlock' block.
 *
 * @Block(
 *  id = "advanced_search_block",
 *  admin_label = @Translation("Advanced Search block"),
 * )
 */
class AdvancedSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('\Drupal\liiweb_search\Form\AdvancedSearchForm');
  }

}
