<?php

namespace Drupal\liiweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'ContactBlock' block.
 *
 * @Block(
 *  id = "contact_block",
 *  admin_label = @Translation("Contact block"),
 * )
 */
class ContactBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];

    //Address
    $address = [
      'address_1'   => 'Bank Lane Trade Fair Site, Accra, Ghana',
      'address_2'    => 'P.O. Box CT 6931',
      'address_3'   => 'Cantonments',
      'address_4'   => 'Accra, Ghana',
    ];
    foreach ($address as $add => $val) {
      $items['address'][] = [
        '#markup' => '<p> ' . $val . '  </p>',
        '#wrapper_attributes' => [
          'class'             => [strtolower($add), 'list-item',],
        ],
      ];
    }

    //Number
    $numbers = [
      'fas fa-phone-square' => '+23 354 537 1246',
    ];

    foreach ($numbers as $icon => $val) {
      $items['number'][] = [
        '#markup' => '<a href="tel:' . $val . ' " class="icon--phone list-inline-item" > <i class="'. $icon .'"></i>' . $val . ' </a>',
        '#wrapper_attributes' => [
          'class'             => ['list-item ' . strrchr($icon, ' ') . 'item'],
        ],
      ];
    }


    //website email
    $items['website_email'][] = [
      '#markup' => '<a href="https://ghalii.org/" target="_blank" class="icon--website list-inline-item" ><i class="fas fa-globe-africa"></i>ghalii.org</a>',
    ];
    $items['website_email'][] = [
      '#markup' => '<a href="mailto:info@ghalii.org" class="icon--email list-inline-item" ><i class="fas fa-at"></i>info@ghalii.org</a>',
    ];

    //Social media
    $platforms = [
      'fab fa-facebook-square' => 'https://facebook.com/africanlii/',
      'fab fa-twitter-square'    => 'https://twitter.com/AfricanLII',
    ];

    foreach ($platforms as $icon => $url) {

      $items['social_media'][] = [
        '#markup' => '<a href="'.$url. '" class="icon--email list-inline-item" target="_blank"><i class="'. $icon .'"></i>'. preg_replace("#^[^:/.]*[:/]+#i", "", $url) . '</a>',
        '#wrapper_attributes' => [
          'class'             => ['list-item ' . strrchr($icon, ' ') . 'item'],
        ],
      ];
    }


    foreach ($items as $item => $val)  {
      // dump($item);
      $build[$item] = [
        '#theme'      => 'item_list',
        '#type'       => 'ul',
        '#attributes' => [
          'class'     => ['list--item-' . $item ],
        ],
        '#items'      => $items[$item],
      ];
    }
      // dump($items);


    // $build['#theme'] = 'contact_block';
    //  $build['contact_block']['#markup'] = 'Implement ContactBlock.';

    return $build;
  }

}
