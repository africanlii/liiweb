<?php

namespace Drupal\liiweb_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GazettesSearchBlok' block.
 *
 * @Block(
 *  id = "gazettes_search_block",
 *  admin_label = @Translation("Gazettes Search Block"),
 * )
 */
class GazettesSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('\Drupal\liiweb_search\Form\GazettesSearchForm');
  }

}
