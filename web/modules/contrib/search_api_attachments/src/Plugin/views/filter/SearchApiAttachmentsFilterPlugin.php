<?php

namespace Drupal\search_api_attachments\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Excludes/Includes search in attachments too.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_attachments_include_search_in_attachments")
 */
class SearchApiAttachmentsFilterPlugin extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!$this->value) {
      return;
    }
    $fields = $this->query->getFulltextFields();
    if (!empty($fields)) {
      foreach ($fields as $key => $field_name) {
        $prefix = 'saa_';
        if (strpos($field_name, $prefix) === 0) {
          unset($fields[$key]);
        }
      }
      $this->query->setFulltextFields($fields);
    }
  }

}
