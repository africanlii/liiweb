<?php

namespace Drupal\liiweb_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchForm.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_form';
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
      '#placeholder' => 'Search',
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
      ->setRouteParameters(['search_api_fulltext' => $form_state->getValue('search_api_fulltext')]);
    $form_state->setRedirectUrl($url);
  }

}
