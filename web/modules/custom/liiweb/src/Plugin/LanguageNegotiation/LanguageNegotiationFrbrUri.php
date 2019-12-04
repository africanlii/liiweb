<?php

namespace Drupal\liiweb\Plugin\LanguageNegotiation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\liiweb\LiiWebApiUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via FRBR URI.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\liiweb\Plugin\LanguageNegotiation\LanguageNegotiationFrbrUri::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -7,
 *   name = @Translation("FRBR URI or URL"),
 *   description = @Translation("Language from the FRBR URI. Defaults to URL if not on a FRBR URI."),
 * )
 */
class LanguageNegotiationFrbrUri extends LanguageNegotiationUrl implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-frbr-uri';

  /**
   * @var \Drupal\liiweb\LiiWebApiUtils
   */
  protected $liiWebApiUtils;

  /**
   * {@inheritdoc}
   */
  public function __construct(LiiWebApiUtils $liiWebApiUtils) {
    $this->liiWebApiUtils = $liiWebApiUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('liiweb.utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    if (empty($request)) {
      return parent::getLangcode($request);
    }

    $uri = $request->getRequestUri();
    $revision = $this->liiWebApiUtils->getRevisionFromFrbrUri($request->getRequestUri());
    if (!empty($revision)) {
      return $revision->language()->getId();
    }

    return parent::getLangcode($request);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $revision = $this->liiWebApiUtils->getRevisionFromFrbrUri($path);
    if (!empty($revision)) {
      return $path;
    }

    return parent::processOutbound($path, $options, $request, $bubbleable_metadata);
  }

}
