<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'SupportNavBlock' block.
 *
 * @Block(
 *  id = "support_nav_block",
 *  admin_label = @Translation("liiweb: Support Nav Block"),
 *  label = @Translation("Get help"),
 * )
 */
class SupportNavBlock extends BlockBase {

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


    //Help links
    $platforms = [
      'fab fa-comment-dots' => '/feedback',
      'fas fa-at'    => '/contact-us',
      'far fa-question-circle'  => '/help',
      'fas fa-info-circle'  => '/about',
    ];

    foreach ($platforms as $icon => $url) {

      $items['social_media'][] = [
        '#markup' => '<a href="' . $url . '" class="icon--email list-inline-item" ><i class="' . $icon . '"></i></a>',
        '#wrapper_attributes' => [
          'class'             => ['list-item ' . strrchr($icon, ' '). '-item'],
        ],
      ];
    }

    $build = [];
    $build['social_block_inline'] = [
      '#theme'      => 'item_list',
      '#type'       => 'ul',
      '#attributes' => [
        'class'     => ['list-inline', 'support-links-item-list'],
      ],
      '#items'      => $items,
    ];

    return $build;
  }

}
