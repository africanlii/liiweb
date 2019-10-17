<?php

/**
 * @file
 * Hooks provided by the "Search API attachments" module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Determines whether an attachment should be indexed.
 *
 * @param object $file
 *   A file object.
 * @param \Drupal\search_api\Item\ItemInterface $item
 *   The item the file was referenced in.
 * @param string $field_name
 *   The name of the field the file was referenced in.
 *
 * @return bool|null
 *   Return FALSE if the attachment should not be indexed.
 */
function hook_search_api_attachments_indexable($file, \Drupal\search_api\Item\ItemInterface $item, $field_name) {
  // Don't index files on entities owned by our bulk upload bot accounts.
  if (in_array($item->getOriginalObject()->uid, my_module_blocked_uids())) {
    return FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
