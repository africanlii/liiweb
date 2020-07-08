<?php

/**
 * @file
 * Contains liiweb_legislation.module.
 */

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_help().
 */
function liiweb_legislation_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Main module help for the liiweb_legislation module.
    case 'help.page.liiweb_legislation':
      $output = '';
      $output .= '<h3>' . t('About') . '</h3>';
      $output .= '<p>' . t('Provide extra') . '</p>';
      return $output;

    default:
  }
}
// function liiweb_legislation_preprocess_node(&$variables)
// {
//   dump($variables);
//   // decode raw json field into actual json
//   if (array_key_exists('field_toc', $variables['content']) && array_key_exists('0', $variables['content']['field_toc'])) {
//     $data = $variables['content']['field_toc']['0']['#context']['value'];
//     $variables['content']['field_toc']['as_json'] = Json::decode($data);
//     dump($variables['content']['field_toc']['as_json']);
//   }
// }

function liiweb_legislation_preprocess_field__node__field_toc__legislation(&$variables, $hook)
{

  $data = $variables['items'
  ]['0'
  ]['content'
  ]['#context'
  ]['value'
  ];
  // dump($variables);
  $data = Json: :decode($data);

  $items = [];
  $values = [];

  $variables['table_content'
  ] = [
    '#theme'      => 'item_list',
    '#type'       => 'ul',
    '#attributes' => [
      'class'     => ['menu-items'
      ],
    ],
  ];

  foreach ($data as $key => $menu_item) {
    $items[] = liiweb_legislation_write_menu_item($menu_item);
    // dump($menu_item);
  }

$variables['table_content'
  ]['#items'
  ] = $items;
  // dump($variables['table_content']);
}

function liiweb_legislation_write_menu_item($menu_item)
{
  // $link = Link::fromTextAndUrl($menu_item['title'], Url::fromUri($menu_item['url'], array(
  //   'attributes' => array(
  //     'target' => '_blank',
  //     'class'  => [strtolower($menu_item['title'])],
  //   )
  // )));

  $item = [
    '#markup'             => $menu_item['title'
    ],
    '#wrapper_attributes' => [
      'class'             => [strtolower($menu_item['title'
        ]), 'menu-item',
      ],
    ],
  ];

  if (isset($menu_item['children'
  ])) {
    $items = [];
    $menu  = [
      '#theme'      => 'item_list',
      '#type'       => 'ul',
      '#attributes' => [
        'class'     => ['menu-items-children'
        ],
      ],
      '#prefix' => $item,
    ];
    foreach ($menu_item['children'
    ] as $key => $menu_item) {
      // dump($menu_item);
      $items[] = liiweb_legislation_write_menu_item($menu_item['children'
      ]);
    }
    //   $menu['#items'] = $items;
  }
  // dump($value);

  return $item;
}
/**
 * Implements hook_theme().
 */
function liiweb_legislation_theme() {
  $theme = [];
  $path  = drupal_get_path('module', 'liiweb_legislation') . '/templates';

  $theme['field__default_node_field_toc_test'
  ] = [
    'base hook' => 'field',
    'path'      => $path,
  ];
  return $theme;
}
