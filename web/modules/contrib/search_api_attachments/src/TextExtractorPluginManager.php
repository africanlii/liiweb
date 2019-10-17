<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for text extractor plugin managers.
 *
 * @ingroup plugin_api
 */
class TextExtractorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_attachments', $namespaces, $module_handler, 'Drupal\search_api_attachments\TextExtractorPluginInterface', 'Drupal\search_api_attachments\Annotation\SearchApiAttachmentsTextExtractor');
    $this->alterInfo('text_extractor_info');
    $this->setCacheBackend($cache_backend, 'text_extractor_info_plugins');
  }

}
