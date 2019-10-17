<?php

/**
 * @file
 * Adds views hooks.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 */
function search_api_attachments_views_data_alter(&$data) {
  // Add the filter for each Search API's index.
  foreach (Index::loadMultiple() as $index) {
    if (!$index->hasValidTracker() || !$index->status()) {
      continue;
    }
    $key = 'search_api_index_' . $index->id();
    $data[$key]['saa_exclude_attachments'] = [
      'title' => t('Search api attachments filter'),
      'filter' => [
        'title' => t('Exclude search in attachments'),
        'help' => 'Excludes searching in attachments',
        'id' => 'search_api_attachments_include_search_in_attachments',
      ],
    ];
  }
}
