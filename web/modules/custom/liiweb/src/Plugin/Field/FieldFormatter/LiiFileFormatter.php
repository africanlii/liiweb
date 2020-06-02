<?php

namespace Drupal\liiweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Plugin implementation of the 'lii_file' formatter.
 *
 * @FieldFormatter(
 *   id = "lii_file",
 *   label = @Translation("LIIWeb File"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class LiiFileFormatter extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements[0] = [
      '#theme' => 'liiweb_file',
      '#files' => [],
    ];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      /** @var FileInterface $file */
      $file_url = $file->createFileUrl(FALSE);
      $title = $file->getFilename();
      $description = $this->getSetting('use_description_as_link_text') ? $item->description : NULL;
      $elements[0]['#files'][] = [
        'title' => !empty($description) ? $description : $title,
        'extension' => pathinfo($file->getFilename(), PATHINFO_EXTENSION),
        'link' => $file_url,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
