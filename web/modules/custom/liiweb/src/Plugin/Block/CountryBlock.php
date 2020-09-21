<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

    /**
     * Provides a 'Hero Block' block.
     *
     * @Block(
     *   id = "hero_block",
     *   admin_label = @Translation("Countries"),
     *   category = @Translation("Country Block")
     * )
     */
    class CountryBlock extends BlockBase
    {

        /**
         * {@inheritdoc}
         */


    /**
     * {@inheritdoc}
     */
    public function build()
    {
      $build = [];
      $build['country_block'] = [
        '#theme' => 'country_block',
      ];
      return $build;
    }
}
