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
      'address_1'   => '8th floor, Sanlam Building',
      'address_2'    => 'Independance Avenue',
      'address_3'   => 'Windhoek, Namibia',
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
      'fas fa-phone-square' => '+26 (46) 1229 097',
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
      '#markup' => '<a href="https://namiblii.org/" target="_blank" class="icon--website list-inline-item" ><i class="fas fa-globe-africa"></i>namiblii.org</a>',
    ];
    $items['website_email'][] = [
      '#markup' => '<a href="mailto:info@namiblii.org" class="icon--email list-inline-item" ><i class="fas fa-at"></i>info@namiblii.org</a>',
    ];

    //Social media
    $platforms = [
      'fab fa-facebook-square' => 'https://facebook.com/namiblii/',
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
