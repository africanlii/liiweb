<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\file\Entity\File;

/**
 * Provides an interface for a plugin that extracts files content.
 *
 * @ingroup plugin_api
 */
interface TextExtractorPluginInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Extract method.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file object.
   *
   * @return string
   *   The file extracted content.
   */
  public function extract(File $file);

}
