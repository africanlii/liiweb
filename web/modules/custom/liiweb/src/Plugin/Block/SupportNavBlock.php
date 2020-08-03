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
      'Feedback, fal fa-comment-dots' => '/feedback',
      'Contact, fal fa-at'    => '/contact',
      'Help, fal fa-question-circle'  => '/help',
      'About, fal fa-info-circle'  => '/about',
    ];

    foreach ($platforms as $icon => $url) {
      $link_title = strtok($icon, ',');
      $items['social_media'][] = [
        '#markup' => '<a href="' . $url . '" class="icon--'. $link_title .' list-inline-item" ><i class="' . $icon . '"></i>'. $link_title .'</a>',
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
