<?php

/**
 * @file
 * Contains organisation_entity.page.inc.
 *
 * Page callback for Organisation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Organisation templates.
 *
 * Default template: organisation_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_organisation_entity(array &$variables) {
  // Fetch OrganisationEntity Entity Object.
  $organisation_entity = $variables['elements']['#organisation_entity'];

  $variables['view_mode'] = $variables['elements']['#view_mode'];
  
  $variables['organisation_entity'] = $organisation_entity;
  
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
  
  $variables['content']['#attached']['library'][] = 'rw_organisation/entity_display';
  
  // Add some useful attributes
  $variables['attributes']['id'] = 'organisation-' . $organisation_entity->id();
  
  // kint($variables);
}
