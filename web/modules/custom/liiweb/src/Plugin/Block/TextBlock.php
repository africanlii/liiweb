<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TextBlock' block.
 *
 * @Block(
 *  id = "text_block",
 *  admin_label = @Translation("Text block"),
 * )
 */
class TextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text'),
      '#default_value' => isset($this->configuration['text']['value']) ? $this->configuration['text']['value'] : NULL,
      '#format'        => isset($this->configuration['text']['format']) ? $this->configuration['text']['format'] : filter_default_format(),
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['text'] = $form_state->getValue('text');
  }

  public function build() {
    $build = [];
    $build['text_block'] = [
      '#theme' => 'text_block',
      '#text' => $this->configuration['text']['value'],
    ];

    return $build;
  }

}
