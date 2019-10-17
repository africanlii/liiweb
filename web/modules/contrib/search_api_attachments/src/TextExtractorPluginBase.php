<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Base class for plugins able to extract file content.
 *
 * @ingroup plugin_api
 */
abstract class TextExtractorPluginBase extends PluginBase implements TextExtractorPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'search_api_attachments.admin_config';

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Mime type guesser service.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, MimeTypeGuesserInterface $mime_type_guesser, MessengerInterface $messenger, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('config.factory'), $container->get('stream_wrapper_manager'), $container->get('file.mime_type.guesser'), $container->get('messenger'), $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function extract(File $file) {
    $filename = $file;
    $mode = 'r';
    return fopen($filename, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getmessenger() {
    return $this->messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration += $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $extractor_plugin_id = $form_state->getValue('extraction_method');
    $config = $this->configFactory->getEditable(static::CONFIGNAME);
    $config->set($extractor_plugin_id . '_configuration', $this->configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Helper method to get the real path from an uri.
   *
   * @param string $uri
   *   The URI of the file, e.g. public://directory/file.jpg.
   *
   * @return mixed
   *   The real path to the file if it is a local file. An URL otherwise.
   */
  public function getRealpath($uri) {
    $wrapper = $this->streamWrapperManager->getViaUri($uri);
    $scheme = $this->fileSystem->uriScheme($uri);
    $local_wrappers = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
    if (in_array($scheme, array_keys($local_wrappers))) {
      return $wrapper->realpath();
    }
    else {
      return $wrapper->getExternalUrl();
    }
  }

  /**
   * Helper method to get the PDF MIME types.
   *
   * @return array
   *   An array of the PDF MIME types.
   */
  public function getPdfMimeTypes() {
    $pdf_mime_types = [];
    $pdf_mime_types[] = $this->mimeTypeGuesser->guess('dummy.pdf');
    return $pdf_mime_types;
  }

}
