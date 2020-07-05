<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'SocialBlock' block.
 *
 * @Block(
 *  id = "social_block",
 *  admin_label = @Translation("liiweb: Social list"),
 *  label = @Translation("Follow us"),
 * )
 */
class SocialBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'inline' => 1,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['inline'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Inline list'),
      '#description' => $this->t('Check this box to display the list in a row, instead of a column'),
      '#default_value' => $this->configuration['inline'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['inline'] = $form_state->getValue('inline');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @TODO: Make these configureable
    // @TODO: Make these have actual links to social media


    //Social media
    $platforms = [
      'fa-facebook-square' => 'https://facebook.com/africanlii/',
      'fa-twitter-square'    => 'https://twitter.com/AfricanLII',
      'fa-linkedin'  => 'https://instagram.com/AfricanLII',
    ];

    foreach ($platforms as $icon => $url) {

      $items['social_media'][] = [
        '#markup' => '<a href="' . $url . '" class="icon--email list-inline-item" target="_blank"><i class="fab ' . $icon . '"></i>' . preg_replace("#^[^:/.]*[:/]+#i", "", $url) . '</a>',
        '#wrapper_attributes' => [
          'class'             => ['list-item item-' . $icon],
        ],
      ];
    }

    $build = [];
    $build['social_block_inline'] = [
      '#theme'      => 'item_list',
      '#type'       => 'ul',
      // '#title'      => t('Follow us'),
      '#attributes' => [
        'class'     => ['list-inline', 'social-item-list'],
      ],
      '#items'      => $items,
    ];

    return $build;
  }

}
