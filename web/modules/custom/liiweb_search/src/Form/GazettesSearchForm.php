<?php

namespace Drupal\liiweb_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchForm.
 */
class GazettesSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gazettes_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#placeholder' => 'Start by searching for a government gazette...',
      '#required' => TRUE
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = \Drupal\Core\Url::fromRoute('view.liiweb_search.page_search')
      ->setRouteParameters(['search_api_fulltext' => $form_state->getValue('search_api_fulltext'), 'f[0]' => 'content_type:government_gazette']);
    $form_state->setRedirectUrl($url);
  }

}
