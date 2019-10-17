<?php

namespace Drupal\xmlsitemap_custom\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Builds the list table for all custom links.
 */
class XmlSitemapCustomListController extends ControllerBase {

  /**
   * Renders a list with all custom links.
   *
   * @return array
   *   The list to be rendered.
   */
  public function render() {
    $build['xmlsitemap_add_custom'] = [
      '#type' => 'link',
      '#title' => t('Add custom link'),
      '#href' => 'admin/config/search/xmlsitemap/custom/add',
    ];
    $header = [
      'loc' => ['data' => t('Location'), 'field' => 'loc', 'sort' => 'asc'],
      'priority' => ['data' => t('Priority'), 'field' => 'priority'],
      'changefreq' => ['data' => t('Change frequency'), 'field' => 'changefreq'],
      'language' => ['data' => t('Language'), 'field' => 'language'],
      'operations' => ['data' => t('Operations')],
    ];

    $rows = [];

    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->execute();

    foreach ($result as $link) {
      $language = $this->languageManager()->getLanguage($link->language);
      $row = [];
      $row['loc'] = Link::fromTextAndUrl($link->loc, Url::fromUri('internal:' . $link->loc));
      $row['priority'] = number_format($link->priority, 1);
      $row['changefreq'] = $link->changefreq ? Unicode::ucfirst(xmlsitemap_get_changefreq($link->changefreq)) : t('None');
      if (isset($header['language'])) {
        $row['language'] = $language->getName();
      }
      $operations['edit'] = [
        'title' => t('Edit'),
        'url' => Url::fromRoute('xmlsitemap_custom.edit', ['link' => $link->id]),
      ];
      $operations['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('xmlsitemap_custom.delete', ['link' => $link->id]),
      ];
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      $rows[] = $row;
    }

    // @todo Convert to tableselect
    $build['xmlsitemap_custom_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No custom links available. <a href="@custom_link">Add custom link</a>', [
        '@custom_link' => Url::fromRoute('xmlsitemap_custom.add', [], [
          'query' => $this->getDestinationArray(),
        ])->toString(),
      ]),
    ];
    $build['xmlsitemap_custom_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
