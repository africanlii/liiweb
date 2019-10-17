<?php

namespace Drupal\search_api_attachments\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Tasks for Search API Attachments.
 *
 * @QueueWorker(
 *   id = "search_api_attachments",
 *   title = @Translation("Extractor Queue"),
 *   cron = {"time" = 180}
 * )
 */
class ExtractorQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Text extractor service.
   *
   * @var \Drupal\search_api_attachments\TextExtractorPluginManager
   */
  protected $textExtractorPluginManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Key value service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TextExtractorPluginManager $text_extractor_plugin_manager, EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $key_value, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->textExtractorPluginManager = $text_extractor_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.search_api_attachments.text_extractor'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('logger.channel.search_api_attachments')
    );
  }

  /**
   * Get the extractor plugin.
   *
   * @return object
   *   The plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getExtractorPlugin() {
    // Get extractor configuration.
    $config = \Drupal::config(FilesExtractor::CONFIGNAME);
    $extractor_plugin_id = $config->get('extraction_method');
    $configuration = $config->get($extractor_plugin_id . '_configuration');

    // Get extractor plugin.
    return $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $extractor_plugin = $this->getExtractorPlugin();

    // Load file from queue item.
    $file = $this->entityTypeManager->getStorage('file')->load($data->fid);
    if ($file === NULL) {
      return;
    }

    try {
      $collection = 'search_api_attachments';
      $key = $collection . ':' . $file->id();

      // Skip file if element is found in key_value collection.
      $extracted_data = $this->keyValue->get($collection)->get($key);
      if (empty($extracted_data)) {
        // Extract file and save it in key_value collection.
        $extracted_data = $extractor_plugin->extract($file);
        $this->keyValue->get($collection)->set($key, $extracted_data);
      }

      $fallback_collection = $this->keyValue->get(FilesExtractor::FALLBACK_QUEUE_KV);
      $fallback_collection->delete($data->entity_type . ':' . $data->entity_id);

      $entity = $this->entityTypeManager->getStorage($data->entity_type)
        ->load($data->entity_id);
      $indexes = ContentEntity::getIndexesForEntity($entity);

      $item_ids = [];
      if (is_a($entity, TranslatableInterface::class)) {
        $translations = $entity->getTranslationLanguages();
        foreach ($translations as $translation_id => $translation) {
          $item_ids[] = $entity->id() . ':' . $translation_id;
        }
      }

      $datasource_id = 'entity:' . $data->entity_type;
      foreach ($indexes as $index) {
        $index->trackItemsUpdated($datasource_id, $item_ids);
      }
    }
    catch (\Exception $exception) {
      if ($data->extract_attempts < 5) {
        $data->extract_attempts++;
        \Drupal::queue('search_api_attachments')->createItem($data);
      }
      else {
        $message_params = [
          '@file_id' => $data->fid,
          '@entity_id' => $data->entity_id,
          '@entity_type' => $data->entity_type,
        ];
        $this->logger->log(LogLevel::ERROR, 'Text extraction failed after 5 attempts @file_id for @entity_type @entity_id.', $message_params);
      }
    }
  }

}
