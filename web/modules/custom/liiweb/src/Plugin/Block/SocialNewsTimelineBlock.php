<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'SocialNewsTimelineBlock' block.
 *
 * @Block(
 *  id = "social_news_timeline",
 *  admin_label = @Translation("Social News timeline"),
 * )
 */
class SocialNewsTimelineBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    $configuration = [
      //      'facebook_app_id'        => '',
      //      'facebook_window_width'  => 300,
      'facebook_window_height' => 500,
      'twitter_account_name'  => '',
      'twitter_window_width'  => 300,
      'twitter_window_height' => 600,
      'twitter_theme_color'   => 'light',
    ];

    return $configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form['facebook_page_url'] = [
      '#type'          => 'url',
      '#title'         => $this->t('Page URL'),
      '#description'   => $this->t('The full URL to the page on Facebook, including https://facebook.com.'),
      '#default_value' => (isset($this->configuration['facebook_page_url']) && !empty($this->configuration['facebook_page_url'])) ? $this->configuration['facebook_page_url'] : NULL,
      '#attributes'    => [
        'placeholder'  => $this->t('E.g. "https://www.facebook.com/Rogerwilco.digital/"'),
      ],
      '#required'      => TRUE,
    ];

    $form['facebook_app_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('App ID'),
      '#description'   => $this->t('Provide the Facebook Application ID number. Get this from your Facebook developers platform'),
      '#default_value' => (isset($this->configuration['facebook_app_id']) && !empty($this->configuration['facebook_app_id'])) ? $this->configuration['facebook_app_id'] : NULL,
      '#weight'        => '0',
    ];

    $form['twitter_account_name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter account name'),
      '#description'   => $this->t('Provide the https://twitter.com user handle for this timeline'),
      '#default_value' => $this->configuration['twitter_account_name'],
      '#maxlength'     => 80,
      '#size'          => 40,
      '#field_prefix'  => '@',
      '#required'      => TRUE,
      '#weight'        => '1',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $this->configuration['facebook_page_url'] = $form_state->getValue('facebook_page_url');
    $this->configuration['facebook_app_id'] = $form_state->getValue('facebook_app_id');

    $this->configuration['twitter_account_name']  = $form_state->getValue('twitter_account_name');
    $this->configuration['twitter_window_width']  = $form_state->getValue('twitter_window_width');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $build = [];


    $build['social_news_timeline'] = [
      '#theme'             => 'social_news_timeline',
      '#facebook_page_url' => $this->configuration['facebook_page_url'],
      '#facebook_app_id'   => $this->configuration['facebook_app_id'],

      '#attached' => [
        'library' => ['jasm/socia_news_timeline'],
      ],
      '#twitter_handle' => $this->configuration['twitter_account_name'],
      '#width'          => $this->configuration['twitter_window_width'],
    ];
    // dd($build);
    return $build;
  }
}
