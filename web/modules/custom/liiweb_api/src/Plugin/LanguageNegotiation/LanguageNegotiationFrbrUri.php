<?php

namespace Drupal\liiweb_api\Plugin\LanguageNegotiation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\liiweb_api\LiiWebApiUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via FRBR URI.
 *
 * @LanguageNegotiation(
 *   id = "language-frbr-uri",
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -7,
 *   name = @Translation("FRBR URI"),
 *   description = @Translation("Language from the FRBR URI"),
 * )
 */
class LanguageNegotiationFrbrUri extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\liiweb_api\LiiWebApiUtils
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
    return new static($container->get('liiweb_api.utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $uri = $request->getRequestUri();
    $revision = $this->liiWebApiUtils->getRevisionFromFrbrUri($request->getRequestUri());
    if (empty($revision)) {
      return FALSE;
    }

    return $revision->language()->getId();
  }

}
