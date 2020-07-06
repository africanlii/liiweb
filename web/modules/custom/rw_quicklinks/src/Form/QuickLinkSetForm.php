<?php

namespace Drupal\rw_quicklinks\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Quicklink set edit forms.
 *
 * @ingroup rw_quicklinks
 */
class QuickLinkSetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\rw_quicklinks\Entity\QuickLinkSet */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Quicklink set.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Quicklink set.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.quick_link_set.canonical', ['quick_link_set' => $entity->id()]);
  }

}
