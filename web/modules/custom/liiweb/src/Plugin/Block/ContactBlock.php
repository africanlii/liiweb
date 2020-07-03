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
      'address_1'   => 'Address Line 1',
      'address_2'    => 'Address Line 2',
      'address_3'   => 'Address Line 3',
      'address_4'    => 'Address Line 4',
    ];
    foreach ($address as $add => $val) {
      $items['address'][] = [
        '#markup' => '<p> ' . $val . '  </p>',
      ];
    }

    //Number
    $numbers = [
      'number_1' => '+27 (xx) xxxx xxx',
    ];

    foreach ($numbers as $numb => $val) {
      $items['number'][] = [
        '#markup' => '<a href="tel:' . $val . ' " class="icon--phone list-inline-item" >' . $val . ' </a>',
      ];
    }


    //website email
    $items['website_email'][] = [
      '#markup' => '<a href="www.liiaddress.org.za" class="icon--website list-inline-item" >www.liiaddress.org.za</a>',
    ];
    $items['website_email'][] = [
      '#markup' => '<a href="mailto:info@liiaddress.org.za" class="icon--email list-inline-item" >info@liiaddress.org.za</a>',
    ];

    //Social media
    $platforms = [
      'Facebook'   => 'https://www.facebook.com/African-Unity-Life-Limited-1151981548191155/',
      'YouTube'    => 'https://www.youtube.com/',
      'Instagram'  => 'https://www.instagram.com/',
    ];

    foreach ($platforms as $service => $url) {
      $link = Link::fromTextAndUrl($service, Url::fromUri($url, array(
        'attributes' => array(
          'target' => '_blank',
          'class'  => [strtolower($service)],
        )
    )));

      $items['social_media'][] = [
        '#markup'             => $link->toString(),
        '#wrapper_attributes' => [
          'class'             => [strtolower($service), 'list-inline-item',],
        ],
      ];
    }


    foreach ($items as $item => $val)  {
      // dump($item);
      $build[$item] = [
        '#theme'      => 'item_list',
        '#type'       => 'ul',
        // '#title'      => t('Follow us'),
        '#attributes' => [
          'class'     => ['list-'. $item . 'list--item' ],
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
