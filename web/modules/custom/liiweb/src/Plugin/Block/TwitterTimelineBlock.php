<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TwitterTimelineBlock' block.
 *
 * @Block(
 *  id = "jasm_twitter_timeline",
 *  admin_label = @Translation("Twitter timeline"),
 * )
 */
class TwitterTimelineBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    // @TODO: Load all the JASM config entities and check if a Twitter preset exists
    $configuration = [
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
    $form['twitter_window_width'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Width'),
      '#description'   => $this->t('Provide the width (in pixels) the timeline widget should display as'),
      '#default_value' => $this->configuration['twitter_window_width'],
      '#field_suffix'  => 'px',
      '#weight'        => '3',
    ];
    $form['twitter_window_height'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Width'),
      '#description'   => $this->t('Provide the width (in pixels) the timeline widget should display as'),
      '#default_value' => $this->configuration['twitter_window_height'],
      '#field_suffix'  => 'px',
      '#weight'        => '5',
    ];
    $form['twitter_theme_color'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Theme'),
      '#description'   => $this->t('Select which theme color to use, e.g. Dark'),
      '#default_value' => $this->configuration['twitter_theme_color'],
      '#options'       => [
        'light'        => $this->t('Light'),
        'dark'         => $this->t('Dark'),
      ],
      '#required'      => TRUE,
      '#weight'        => '7',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $this->configuration['twitter_account_name']  = $form_state->getValue('twitter_account_name');
    $this->configuration['twitter_window_width']  = $form_state->getValue('twitter_window_width');
    $this->configuration['twitter_window_height'] = $form_state->getValue('twitter_window_height');
    $this->configuration['twitter_theme_color']   = $form_state->getValue('twitter_theme_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Attach the library to load the Twitter API asynchronously
    $build = [
      '#theme'    => 'jasm_twitter_timeline',
      '#attached' => [
        'library' => ['jasm/twitter_timeline'],
      ],
      '#twitter_handle' => $this->configuration['twitter_account_name'],
      '#width'          => $this->configuration['twitter_window_width'],
      '#height'         => $this->configuration['twitter_window_height'],
      '#theme_color'    => $this->configuration['twitter_theme_color'],
    ];

    //    $build['#conten'][] = $this->configuration['twitter_account_name'];
    //    $build['#conten'][] = $this->configuration['twitter_window_width'];

    return $build;
  }
}
