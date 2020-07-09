<?php

namespace Drupal\liiweb_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\node\Entity\NodeType;

/**
 * Class AdvancedSearchForm.
 */
class AdvancedSearchForm extends FormBase {

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

    $types =  $this::NodeTypes();
    $types['All'] = 'Search Everything';
    ;
    // dump(asort($types));
    $form['type'] = [
      '#type' => 'select',
      '#description' => "Search Everything",
      '#options' => $types,
    ];

    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => $this->configuration['search_api_fulltext'],
      '#placeholder' => 'Search',
      '#required' => TRUE
    ];
    // dump($types);
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
          ->setRouteParameters(['type' => $form_state->getValue('type'),'search_api_fulltext' => $form_state->getValue('search_api_fulltext')]);
    $form_state->setRedirectUrl($url);
  }

  public static function NodeTypes()
  {
    $types = array();
    $content_types = NodeType::loadMultiple();

    if (!empty($content_types)) {
      foreach ($content_types as $type => $details) {
        $types[$details->id()] = $details->label();
      }
      asort($types);
    }

    return $types;
  }
}
