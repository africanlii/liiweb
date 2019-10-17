<?php

namespace Drupal\search_api_attachments\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\search_api\Processor\ProcessorPluginManager;
use Drupal\search_api_attachments\ExtractFileValidator;
use Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File formatter displaying text extracted form attachment document.
 *
 * @FieldFormatter(
 *   id = "file_extracted_text",
 *   label = @Translation("Text extracted from attachment"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ExtractedText extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Files extractor config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Search API Processor Plugin Manager.
   *
   * @var \Drupal\search_api\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Search API Attachments Text Extractor Plugin Manager.
   *
   * @var \Drupal\search_api_attachments\TextExtractorPluginManager
   */
  protected $textExtractorPluginManager;

  /**
   * FilesExtractor processor plugin.
   *
   * @var \Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor
   */
  protected $extractor;

  /**
   * Extraction plugin.
   *
   * @var \Drupal\search_api_attachments\TextExtractorPluginInterface
   */
  protected $extractionMethod;

  /**
   * The extract file validator service.
   *
   * @var \Drupal\search_api_attachments\ExtractFileValidator
   */
  protected $extractFileValidator;

  /**
   * ExtractedText constructor.
   *
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definitions.
   * @param array $settings
   *   The settings.
   * @param string $label
   *   The label.
   * @param string $viewMode
   *   The view mode.
   * @param array $thirdPartySettings
   *   The third party settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\search_api\Processor\ProcessorPluginManager $processorPluginManager
   *   The processor plugin manager.
   * @param \Drupal\search_api_attachments\TextExtractorPluginManager $textExtractorPluginManager
   *   The text extractor plugin manager.
   * @param \Drupal\Core\Config\Config $config
   *   The configuration.
   * @param \Drupal\search_api_attachments\ExtractFileValidator $extractFileValidator
   *   The extract file validator.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, $label, $viewMode, array $thirdPartySettings, ModuleHandlerInterface $moduleHandler, ProcessorPluginManager $processorPluginManager, TextExtractorPluginManager $textExtractorPluginManager, Config $config, ExtractFileValidator $extractFileValidator) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings);

    $this->moduleHandler = $moduleHandler;
    $this->processorPluginManager = $processorPluginManager;
    $this->textExtractorPluginManager = $textExtractorPluginManager;
    $this->config = $config;
    $this->extractFileValidator = $extractFileValidator;

    $extractorPluginId = $this
      ->config
      ->get('extraction_method');
    $configuration = $this
      ->config
      ->get($extractorPluginId . '_configuration');
    $this->extractionMethod = $this
      ->textExtractorPluginManager
      ->createInstance($extractorPluginId, $configuration);

    $this->extractor = $this
      ->processorPluginManager
      ->createInstance('file_attachments');;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $pluginDefinition) {
    return new static(
      $plugin_id,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('module_handler'),
      $container->get('plugin.manager.search_api.processor'),
      $container->get('plugin.manager.search_api_attachments.text_extractor'),
      $container->get('config.factory')->get(FilesExtractor::CONFIGNAME),
      $container->get('search_api_attachments.extract_file_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $host_entity = $items->getParent()->getValue();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($contents = $this->extractFileContents($host_entity, $file)) {
        $elements[$delta] = [
          '#markup' => $contents,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * Extracts content of given file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the file is attached to.
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   *
   * @return string|null
   *   Content of the file or NULL if type of file is not supported.
   */
  protected function extractFileContents(EntityInterface $entity, File $file) {
    if ($this->isFileIndexable($file)) {
      return $this
        ->extractor
        ->extractOrGetFromCache($entity, $file, $this->extractionMethod);
    }
    return NULL;
  }

  /**
   * Check if the file is allowed to be indexed.
   *
   * @param object $file
   *   A file object.
   *
   * @return bool
   *   TRUE or FALSE
   */
  protected function isFileIndexable($file) {
    // This method is a copy of
    // Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor::isFileIndexable()
    // and differs mostly in the signature. Unfortunately it can't be used here
    // as it requires second argument of type
    // \Drupal\search_api\Item\ItemInterface.
    // File should exist in disc.
    $indexable = file_exists($file->getFileUri());
    if (!$indexable) {
      return FALSE;
    }
    // File should have a mime type that is allowed.
    $excluded_extensions_array = explode(' ', $this->getSetting('excluded_extensions'));
    $all_excluded_mimes = $this->extractFileValidator->getExcludedMimes($excluded_extensions_array);
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
    $max_filesize = $this->getSetting('max_filesize');
    $indexable = $indexable && $this->extractFileValidator->isFileSizeAllowed($file, $max_filesize);
    if (!$indexable) {
      return FALSE;
    }
    // Whether a private file can be indexed or not.
    $excluded_private = $this->getSetting('excluded_private');
    $indexable = $indexable && $this->extractFileValidator->isPrivateFileAllowed($file, $excluded_private);
    if (!$indexable) {
      return FALSE;
    }
    $result = $this->moduleHandler->invokeAll(
      'search_api_attachments_indexable',
      [$file]
    );
    $indexable = !in_array(FALSE, $result, TRUE);
    return $indexable;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'excluded_extensions' => ExtractFileValidator::DEFAULT_EXCLUDED_EXTENSIONS,
      'max_filesize' => '0',
      'excluded_private' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['excluded_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded file extensions'),
      '#default_value' => $this->getSetting('excluded_extensions'),
      '#size' => 80,
      '#maxlength' => 255,
      '#description' => $this->t('File extensions that are excluded from indexing. Separate extensions with a space and do not include the leading dot.<br />Example: "aif art avi bmp gif ico mov oga ogv png psd ra ram rgb flv"<br />Extensions are internally mapped to a MIME type, so it is not necessary to put variations that map to the same type (e.g. tif is sufficient for tif and tiff)'),
    ];
    $form['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => $this->getSetting('max_filesize'),
      '#description' => $this->t('Enter a value like "10 KB", "10 MB" or "10 GB" in order to restrict the max file size of files that should be indexed.<br /> Enter "0" for no limit restriction.'),
      '#size' => 10,
    ];
    $form['excluded_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude private files'),
      '#default_value' => $this->getSetting('excluded_private'),
      '#description' => $this->t('Check this box if you want to exclude private files from being indexed.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Excluded file extensions: @extensions', ['@extensions' => $this->getSetting('excluded_extensions')]);
    $summary[] = $this->t('Maximum upload size: @maxsize', ['@maxsize' => $this->getSetting('max_filesize')]);
    $isexcluded = $this->getSetting('excluded_private') ? 'true' : 'false';
    $summary[] = $this->t('Exclude private files: @isexcluded', ['@isexcluded' => $isexcluded]);
    return $summary;
  }

}
