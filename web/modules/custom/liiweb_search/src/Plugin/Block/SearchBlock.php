<?php

namespace Drupal\liiweb_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchBlock' block.
 *
 * @Block(
 *  id = "search_block",
 *  admin_label = @Translation("Search block"),
 * )
 */
class SearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\liiweb_search\Form\SearchForm');
    // Remove CSRF tokens so they don't appear in URL
    // Mandatory disclaimer, should be OK though
    // https://stackoverflow.com/questions/1497298/preventing-form-token-from-rendering-in-drupal-get-forms
    unset($form['form_build_id']);
    unset($form['form_id']);
    return $form;
  }

}
