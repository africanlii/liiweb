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


    $items[] = [
        '#markup' => '<a href="/" class="icon-- list-inline-item" >Chat</a>',
      ];
    $items[] = [
      '#markup' => '<a href="/" class="icon-- list-inline-item" >Email</a>',
    ];
    $items[] = [
      '#markup' => '<a href="/" class="icon-- list-inline-item" >Help</a>',
    ];
    $items[] = [
      '#markup' => '<a href="/" class="icon-- list-inline-item" >Question</a>',
    ];

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
