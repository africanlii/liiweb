<?php

namespace Drupal\liiweb_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Query\ResultSetInterface;
use Solarium\QueryType\Select\Result\Result;


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
    $types['All'] = 'Search everything';
    $not_types = ['page','billboard'];
      unset($types['page']);
      unset($types['billboard']);
      unset($types['article']);

    $this::move_to_top($types, 'All');
    // $test = ;
    // dump($test);

    $form['type_1'] = [
      '#type' => 'select',
      '#default_value' => \Drupal::request()->query->get('type_1'),
      '#options' => $types,
    ];

    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => \Drupal::request()->query->get('search_api_fulltext'),
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
          ->setRouteParameters(['type_1' => $form_state->getValue('type_1'),'search_api_fulltext' => $form_state->getValue('search_api_fulltext')]);
    $form_state->setRedirectUrl($url);
  }


  public function move_to_top(&$array, $key)
  {
    $temp = array($key => $array[$key]);
    unset($array[$key]);
    $array = $temp + $array;
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
