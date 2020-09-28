<?php

namespace Drupal\rw_organisation\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrganisationEntityTypeForm.
 */
class OrganisationEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $organisation_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $organisation_entity_type->label(),
      '#description' => $this->t("Label for the Organisation type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $organisation_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\rw_organisation\Entity\OrganisationEntityType::load',
      ],
      '#disabled' => !$organisation_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $organisation_entity_type = $this->entity;
    $status = $organisation_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Organisation type.', [
          '%label' => $organisation_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Organisation type.', [
          '%label' => $organisation_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($organisation_entity_type->toUrl('collection'));
  }

}
