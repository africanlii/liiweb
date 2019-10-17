<?php

namespace Drupal\search_api_attachments\Plugin\search_api_attachments;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Drupal\file\Entity\File;

/**
 * Provides tika server extractor.
 *
 * @SearchApiAttachmentsTextExtractor(
 *   id = "tika_server_extractor",
 *   label = @Translation("Tika JAX-RS Server Extractor"),
 *   description = @Translation("Adds Tika JAX-RS server extractor support."),
 * )
 */
class TikaServerExtractor extends TextExtractorPluginBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, MimeTypeGuesserInterface $mime_type_guesser, MessengerInterface $messenger, FileSystemInterface $file_system, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $stream_wrapper_manager, $mime_type_guesser, $messenger, $file_system);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file.mime_type.guesser'),
      $container->get('messenger'),
      $container->get('file_system'),
      $container->get('http_client')
    );
  }

  /**
   * Extract file with a Tika JAX-RS Server.
   *
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   *
   * @return string
   *   The text extracted from the file.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function extract(File $file) {
    $data = NULL;
    $options = [
      'timeout' => $this->configuration['timeout'],
      'body' => fopen($file->getFileUri(), 'r'),
      'headers'   => [
        'Accept' => 'text/plain',
      ],
    ];

    $response = $this->httpClient->request('PUT', $this->getServerUri() . '/tika', $options);
    if ($response->getStatusCode() === 200) {
      $data = (string) $response->getBody();
    }
    else {
      throw new \Exception('Tika JAX-RS Server is not available.');
    }

    return $data;
  }

  /**
   * Returns the Tika server URI from the current config.
   *
   * @return string
   *   The full Tika server URI.
   */
  protected function getServerUri() {
    return $this->configuration['scheme'] . '://' . $this->configuration['host'] . ':' . $this->configuration['port'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP protocol'),
      '#description' => $this->t('The HTTP protocol to use for sending queries.'),
      '#default_value' => isset($this->configuration['scheme']) ? $this->configuration['scheme'] : 'http',
      '#options' => [
        'http' => 'http',
        'https' => 'https',
      ],
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tika server host'),
      '#description' => $this->t('The host name or IP of your Tika server, e.g. <code>localhost</code> or <code>www.example.com</code>.'),
      '#default_value' => isset($this->configuration['host']) ? $this->configuration['host'] : 'localhost',
      '#required' => TRUE,
    ];

    $form['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tika server port'),
      '#description' => $this->t('The default port is 9998.'),
      '#default_value' => isset($this->configuration['port']) ? $this->configuration['port'] : '9998',
      '#required' => TRUE,
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Query timeout'),
      '#description' => $this->t('The timeout in seconds for queries sent to the Tika server.'),
      '#default_value' => isset($this->configuration['timeout']) ? $this->configuration['timeout'] : 5,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['port'])) {
      $port = $values['text_extractor_config']['port'];
      if (!is_numeric($port) || $port < 0 || $port > 65535) {
        $form_state->setError($form['text_extractor_config']['port'], $this->t('The port has to be an integer between 0 and 65535.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['scheme'] = $form_state->getValue(['text_extractor_config', 'scheme']);
    $this->configuration['host'] = $form_state->getValue(['text_extractor_config', 'host']);
    $this->configuration['port'] = $form_state->getValue(['text_extractor_config', 'port']);
    $this->configuration['timeout'] = $form_state->getValue(['text_extractor_config', 'timeout']);
    parent::submitConfigurationForm($form, $form_state);
  }

}
