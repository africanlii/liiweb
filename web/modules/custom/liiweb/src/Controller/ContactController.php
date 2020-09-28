<?php

namespace Drupal\liiweb\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ContactController.
 */
class ContactController extends ControllerBase {

  /**
   * Construct the "Contact us" page with code
   *
   * @return array $build.
   *   A render array of render arrays containing the contact webform, followed
   *   by branch (organisation) entities
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function build()
  {
    $build = [
      '#theme'            => 'liiweb_contact',
      '#contact_entities' => [],
    ];

    // Load the "Contact us" webform called "contact" (machine name)
    $contact_form_storage = \Drupal::entityTypeManager()->getStorage('webform')->load('contact');

    if ($contact_form_storage) {
      $contact_form         = $contact_form_storage->getSubmissionForm();

      $build['#contact_entities'][] = $contact_form;
    }


    // kint($contact_form);

    return $build;
  }


}
