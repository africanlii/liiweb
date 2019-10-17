<?php

namespace Drupal\search_api_attachments\Plugin\search_api\processor;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Utility\Error;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api_attachments\ExtractFileValidator;
use Drupal\search_api_attachments\TextExtractorPluginInterface;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides file fields processor.
 *
 * @SearchApiProcessor(
 *   id = "file_attachments",
 *   label = @Translation("File attachments"),
 *   description = @Translation("Adds the file attachments content to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   }
 * )
 */
class FilesExtractor extends ProcessorPluginBase implements PluginFormInterface {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'search_api_attachments.admin_config';

  const FALLBACK_QUEUE_LOCK = 'search_api_attachments_fallback_queue';

  const FALLBACK_QUEUE_KV = 'search_api_attachments:queued';

  /**
   * Name of the "virtual" field that handles file entity type extractions.
   *
   * This is used per example in a File datasource index or mixed
   * datasources index.
   */
  const SAA_FILE_ENTITY = 'saa_file_entity';

  /**
   * Prefix of the properties provided by this module.
   */
  const SAA_PREFIX = 'saa_';

  /**
   * The plugin manager for our text extractor.
   *
   * @var \Drupal\search_api_attachments\TextExtractorPluginManager
   */
  protected $textExtractorPluginManager;

  /**
   * The extract file validator service.
   *
   * @var \Drupal\search_api_attachments\ExtractFileValidator
   */
  protected $extractFileValidator;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Search API field helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldHelper;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TextExtractorPluginManager $text_extractor_plugin_manager, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $key_value, ModuleHandlerInterface $module_handler, FieldsHelperInterface $field_helper, ExtractFileValidator $extractFileValidator, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->textExtractorPluginManager = $text_extractor_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value;
    $this->moduleHandler = $module_handler;
    $this->fieldHelper = $field_helper;
    $this->extractFileValidator = $extractFileValidator;
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
        $container->get('config.factory'),
        $container->get('entity_type.manager'),
        $container->get('keyvalue'),
        $container->get('module_handler'),
        $container->get('search_api.fields_helper'),
        $container->get('search_api_attachments.extract_file_validator'),
        $container->get('logger.channel.search_api_attachments')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      // Add properties for all index available file fields and for file entity.
      foreach ($this->getFileFieldsAndFileEntityItems() as $field_name => $label) {
        $definition = [
          'label' => $this->t('Search api attachments: @label', ['@label' => $label]),
          'description' => $this->t('Search api attachments: @label', ['@label' => $label]),
          'type' => 'string',
          'processor_id' => $this->getPluginId(),
        ];
        $properties[static::SAA_PREFIX . $field_name] = new ProcessorProperty($definition);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $files = [];
    $config = $this->configFactory->get(static::CONFIGNAME);
    $extractor_plugin_id = $config->get('extraction_method');
    if ($extractor_plugin_id != '') {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      // Get the entity.
      $entity = $item->getOriginalObject()->getValue();
      $is_entity_type_file = $entity->getEntityTypeId() == 'file';
      foreach ($this->getFileFieldsAndFileEntityItems() as $field_name => $label) {
        // If the parent entity is not a file, no need to parse the
        // saa static::SAA_FILE_ENTITY item.
        if (!$is_entity_type_file && $field_name == static::SAA_FILE_ENTITY) {
          break;
        }
        if ($is_entity_type_file && $field_name == static::SAA_FILE_ENTITY) {
          $files[] = $entity;
        }

        $property_path = static::SAA_PREFIX . $field_name;

        // A way to load $field.
        foreach ($this->fieldHelper->filterForPropertyPath($item->getFields(), NULL, $property_path) as $field) {
          $all_fids = [];
          if ($entity->hasField($field_name)) {
            // Get type to manage media entity reference case.
            $type = $entity->get($field_name)->getFieldDefinition()->getType();
            if ($type == 'entity_reference') {
              /** @var \Drupal\Core\Field\BaseFieldDefinition $field_def */
              $field_def = $entity->get($field_name)->getFieldDefinition();
              if ($field_def->getItemDefinition()->getSetting('target_type') === 'media') {
                // This is a media field.
                $filefield_values = $entity->get($field_name)->filterEmptyItems()->getValue();
                foreach ($filefield_values as $media_value) {
                  $media = Media::load($media_value['target_id']);
                  if ($media !== NULL) {
                    $bundle_configuration = $media->getSource()->getConfiguration();
                    if (isset($bundle_configuration['source_field'])) {
                      /** @var \Drupal\Core\Field\FieldItemListInterface $field_item */
                      foreach ($media->get($bundle_configuration['source_field'])->filterEmptyItems() as $field_item) {
                        if ($field_item->getFieldDefinition()->getType() === 'file') {
                          $value = $field_item->getValue();
                          $all_fids[] = $value['target_id'];
                        }
                      }
                    }
                  }
                }
              }
            }
            elseif ($type == "file") {
              $filefield_values = $entity->get($field_name)->filterEmptyItems()->getValue();
              foreach ($filefield_values as $filefield_value) {
                $all_fids[] = $filefield_value['target_id'];
              }
            }

            $fids = $this->limitToAllowedNumber($all_fids);
            // Retrieve the files.
            $files = $this->entityTypeManager
              ->getStorage('file')
              ->loadMultiple($fids);
          }
          if (!empty($files)) {
            $extraction = '';

            foreach ($files as $file) {
              if ($this->isFileIndexable($file, $item, $field_name)) {
                $extraction .= $this->extractOrGetFromCache($entity, $file, $extractor_plugin);
              }
            }
            $field->addValue($extraction);
          }
        }
      }
    }
  }

  /**
   * Extract non text file data or get it from cache if available and cache it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the file is attached to.
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   * @param \Drupal\search_api_attachments\TextExtractorPluginInterface $extractor_plugin
   *   The plugin used to extract file content.
   *
   * @return string
   *   $extracted_data
   */
  public function extractOrGetFromCache(EntityInterface $entity, File $file, TextExtractorPluginInterface $extractor_plugin) {
    // Directly process plaintext files.
    if (substr($file->getMimeType(), 0, 5) == 'text/') {
      return file_get_contents($file->getFileUri());
    }
    $collection = 'search_api_attachments';
    $key = $collection . ':' . $file->id();
    $extracted_data = '';
    if ($cache = $this->keyValue->get($collection)->get($key)) {
      $extracted_data = $cache;
    }
    else {
      try {
        // Only extract if this file has not previously failed and was queued.
        $fallback_collection = $this->keyValue->get(FilesExtractor::FALLBACK_QUEUE_KV);
        $queued_files = $fallback_collection->get($file->id());
        if (empty($queued_files[$entity->getEntityTypeId()][$entity->id()])) {
          $extracted_data = $extractor_plugin->extract($file);
          $extracted_data = $this->limitBytes($extracted_data);
          $this->keyValue->get($collection)->set($key, $extracted_data);
        }
        else {
          $this->queueItem($entity, $file);
        }
      }
      catch (\Exception $e) {
        $error = Error::decodeException($e);
        $message_params = [
          '@file_id' => $file->id(),
          '@entity_id' => $entity->id(),
          '@entity_type' => $entity->getEntityTypeId(),
          '@type' => $error['%type'],
          '@message' => $error['@message'],
          '@function' => $error['%function'],
          '@line' => $error['%line'],
          '@file' => $error['%file'],
        ];
        $this->logger->log(LogLevel::ERROR, 'Error extracting text from file @file_id for @entity_type @entity_id. @type: @message in @function (line @line of @file).', $message_params);
        $this->queueItem($entity, $file);
      }
    }
    return $extracted_data;
  }

  /**
   * Queue a failed extraction for later processing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the file is attached to.
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   *
   * @return bool
   *   Success of queueing process.
   */
  private function queueItem(EntityInterface $entity, File $file) {

    if (\Drupal::lock()->acquire(static::FALLBACK_QUEUE_LOCK)) {
      $queued_file_collection = $this->keyValue->get(static::FALLBACK_QUEUE_KV);
      $queued_files = $queued_file_collection->get($file->id());
      $queued_files[$entity->getEntityTypeId()][$entity->id()] = TRUE;
      $queued_file_collection->set($file->id(), $queued_files);
      \Drupal::lock()->release(static::FALLBACK_QUEUE_LOCK);

      // Add file to queue.
      $queue = \Drupal::queue('search_api_attachments');
      $item = new \stdClass();
      $item->fid = $file->id();
      $item->entity_id = $entity->id();
      $item->entity_type = $entity->getEntityTypeId();
      $item->extract_attempts = 1;
      $queue->createItem($item);

      $this->logger->log(LogLevel::INFO, 'File added to the queue for text extraction @file_id for @entity_type @entity_id.', [
        '@file_id' => $file->id(),
        '@entity_id' => $entity->id(),
        '@entity_type' => $entity->getEntityTypeId(),
      ]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Limit the number of items to index per field to the configured limit.
   *
   * @param array $all_fids
   *   Array of fids.
   *
   * @return array
   *   An array of $limit number of items.
   */
  public function limitToAllowedNumber(array $all_fids) {
    $limit = 0;
    if (isset($this->configuration['number_indexed'])) {
      $limit = $this->configuration['number_indexed'];
    }
    // If limit is 0 return all items.
    if ($limit == 0) {
      return $all_fids;
    }
    if (count($all_fids) > $limit) {
      return array_slice($all_fids, 0, $limit);
    }
    else {
      return $all_fids;
    }
  }

  /**
   * Limit the indexed text to first N bytes.
   *
   * @param string $extracted_text
   *   The hole extracted text.
   *
   * @return string
   *   The first N bytes of the extracted text that will be indexed and cached.
   */
  public function limitBytes($extracted_text) {
    $bytes = 0;
    if (isset($this->configuration['number_first_bytes'])) {
      $bytes = Bytes::toInt($this->configuration['number_first_bytes']);
    }
    // If $bytes is 0 return all items.
    if ($bytes == 0) {
      return $extracted_text;
    }
    else {
      $extracted_text = mb_strcut($extracted_text, 0, $bytes);
    }
    return $extracted_text;
  }

  /**
   * Check if the file is allowed to be indexed.
   *
   * @param object $file
   *   A file object.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item the file was referenced in.
   * @param string|null $field_name
   *   The name of the field the file was referenced in, if applicable.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isFileIndexable($file, ItemInterface $item, $field_name = NULL) {
    // File should exist in disc.
    $indexable = file_exists($file->getFileUri());
    if (!$indexable) {
      return FALSE;
    }
    // File should have a mime type that is allowed.
    $all_excluded_mimes = $this->extractFileValidator->getExcludedMimes(NULL, $this->configuration['excluded_mimes']);
    $indexable = $indexable && !in_array($file->getMimeType(), $all_excluded_mimes);
    if (!$indexable) {
      return FALSE;
    }
    // File permanent.
    $indexable = $indexable && $file->isPermanent();
    if (!$indexable) {
      return FALSE;
    }
    // File shouldn't exceed configured file size.
    $max_filesize = $this->configuration['max_filesize'];
    $indexable = $indexable && $this->extractFileValidator->isFileSizeAllowed($file, $max_filesize);
    if (!$indexable) {
      return FALSE;
    }
    // Whether a private file can be indexed or not.
    $excluded_private = $this->configuration['excluded_private'];
    $indexable = $indexable && $this->extractFileValidator->isPrivateFileAllowed($file, $excluded_private);
    if (!$indexable) {
      return FALSE;
    }
    $result = $this->moduleHandler->invokeAll(
        'search_api_attachments_indexable', [$file, $item, $field_name]
    );
    $indexable = !in_array(FALSE, $result, TRUE);
    return $indexable;
  }

  /**
   * Get the file fields of indexed bundles and an entity file general item.
   *
   * @return array
   *   An array of file field with field name as key and label as value and
   *   an element for generic file entity item.
   */
  protected function getFileFieldsAndFileEntityItems() {
    $file_elements = [];

    // Retrieve file fields of indexed bundles.
    foreach ($this->getIndex()->getDatasources() as $datasource) {
      if ($datasource->getPluginId() == 'entity:file') {
        $file_elements[static::SAA_FILE_ENTITY] = $this->t('File entity');
      }
      foreach ($datasource->getPropertyDefinitions() as $property) {
        if ($property instanceof FieldDefinitionInterface) {
          if ($property->getType() == 'file') {
            $file_elements[$property->getName()] = $property->getLabel();
          }
          if ($property->getType() == "entity_reference") {
            if ($property->getSetting('target_type') === 'media') {
              $settings = $property->getItemDefinition()->getSettings();
              if (isset($settings['handler_settings']['target_bundles'])) {
                // For each media bundle allowed, check if the source field is a
                // file field.
                foreach ($settings['handler_settings']['target_bundles'] as $bundle_name) {
                  $bundle_configuration = $this->entityTypeManager->getStorage('media_type')->load($bundle_name)->toArray();
                  if (isset($bundle_configuration['source_configuration']['source_field'])) {
                    $source_field = $bundle_configuration['source_configuration']['source_field'];
                    $field_config = $this->entityTypeManager->getStorage('field_storage_config')->load(sprintf('media.%s', $source_field))->toArray();
                    if (isset($field_config['type']) && $field_config['type'] === 'file') {
                      $file_elements[$property->getName()] = $property->getLabel();
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return $file_elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($this->configuration['excluded_extensions'])) {
      $default_excluded_extensions = $this->configuration['excluded_extensions'];
    }
    else {
      $default_excluded_extensions = ExtractFileValidator::DEFAULT_EXCLUDED_EXTENSIONS;
    }
    $form['excluded_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded file extensions'),
      '#default_value' => $default_excluded_extensions,
      '#size' => 80,
      '#maxlength' => 255,
      '#description' => $this->t('File extensions that are excluded from indexing. Separate extensions with a space and do not include the leading dot.<br />Example: "aif art avi bmp gif ico mov oga ogv png psd ra ram rgb flv"<br />Extensions are internally mapped to a MIME type, so it is not necessary to put variations that map to the same type (e.g. tif is sufficient for tif and tiff)'),
    ];
    $form['number_indexed'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of files indexed per file field'),
      '#default_value' => isset($this->configuration['number_indexed']) ? $this->configuration['number_indexed'] : '0',
      '#size' => 5,
      '#min' => 0,
      '#max' => 999999,
      '#description' => $this->t('The number of files to index per file field.<br />The order of indexation is the weight in the widget.<br /> 0 for no restriction.'),
    ];
    $form['number_first_bytes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit size of the extracted string before indexing.'),
      '#default_value' => isset($this->configuration['number_first_bytes']) ? $this->configuration['number_first_bytes'] : '0',
      '#size' => 5,
      '#min' => 0,
      '#max' => 99999,
      '#description' => $this->t('Enter a value like "1000", "10 KB", "10 MB" or "10 GB" in order to restrict the size of the content after extraction.<br /> 0 to index the full extracted content without bytes limitation.'),
    ];
    $form['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => isset($this->configuration['max_filesize']) ? $this->configuration['max_filesize'] : '0',
      '#description' => $this->t('Enter a value like "10 KB", "10 MB" or "10 GB" in order to restrict the max file size of files that should be indexed.<br /> Enter "0" for no limit restriction.'),
      '#size' => 10,
    ];
    $form['excluded_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude private files'),
      '#default_value' => isset($this->configuration['excluded_private']) ? $this->configuration['excluded_private'] : TRUE,
      '#description' => $this->t('Check this box if you want to exclude private files from being indexed.'),
    ];
    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::validateConfigurationForm()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validate 'number_first_bytes'.
    $number_first_bytes = trim($form_state->getValue('number_first_bytes'));
    $error = $this->validateSize($number_first_bytes);
    if ($error) {
      $form_state->setError($form['number_first_bytes'], $this->t('The size limit option must contain a valid value. You may either enter "0" (for no restriction) or a string like "10 KB", "10 MB" or "10 GB".'));
    }

    // Validate 'max_filesize'.
    $max_filesize = trim($form_state->getValue('max_filesize'));
    $error = $this->validateSize($max_filesize);
    if ($error) {
      $form_state->setError($form['max_filesize'], $this->t('The max filesize option must contain a valid value. You may either enter "0" (for no restriction) or a string like "10 KB", "10 MB" or "10 GB".'));
    }
  }

  /**
   * Helper method to validate the size of files' format.
   *
   * @param string $bytes
   *   Number of bytes.
   *
   * @return bool
   *   TRUE if $bites is of form "N KB", "N MB" or "N GB" where N is integer.
   */
  public function validateSize($bytes) {
    $error = FALSE;
    if ($bytes != '0') {

      $size_info = explode(' ', $bytes);
      // The only case we can have count($size_info) == 1 is for '0' value.
      if (count($size_info) == 1) {
        $error = $size_info[0] != '0';
      }
      elseif (count($size_info) != 2) {
        $error = TRUE;
      }
      else {
        $starts_integer = is_int((int) $size_info[0]);
        $unit_expected = in_array($size_info[1], ['KB', 'MB', 'GB']);
        $error = !$starts_integer || !$unit_expected;
      }
    }
    return $error;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $excluded_extensions = $form_state->getValue('excluded_extensions');
    $excluded_extensions_array = explode(' ', $excluded_extensions);
    $excluded_mimes_array = $this->extractFileValidator->getExcludedMimes($excluded_extensions_array);
    $excluded_mimes_string = implode(' ', $excluded_mimes_array);
    $this->setConfiguration($form_state->getValues() + ['excluded_mimes' => $excluded_mimes_string]);
  }

}
