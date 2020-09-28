<?php

/**
 * @file
 * Contains quick_link_set.page.inc.
 *
 * Page callback for Quicklink set entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Quicklink set templates.
 *
 * Default template: quick_link_set.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_quick_link_set(array &$variables) {
  // Fetch QuickLinkSet Entity Object.
  $quick_link_set = $variables['elements']['#quick_link_set'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
  
  // kint($variables);
}
