<?php

namespace Drupal\liiweb\Routing;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\liiweb\LiiWebUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides the core Drupal\Core\Routing\UrlGenerator.
 *
 * For AKN urls, the '@' character needs to be decoded.
 */
class LiiWebUrlGenerator extends UrlGenerator {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  public function __construct(RouteProviderInterface $provider, OutboundPathProcessorInterface $path_processor, OutboundRouteProcessorInterface $route_processor, RequestStack $request_stack, LiiWebUtils $liiWebUtils, array $filter_protocols = ['http', 'https',]) {
    parent::__construct($provider, $path_processor, $route_processor, $request_stack, $filter_protocols);
    $this->liiWebUtils = $liiWebUtils;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
    $url = parent::generateFromRoute($name, $parameters, $options, $collect_bubbleable_metadata);

    $generatedUrl = $url->getGeneratedUrl();
    if ($this->liiWebUtils->isAknUri(urldecode($generatedUrl))) {
      $url->setGeneratedUrl(str_replace('%40', '@', $generatedUrl));
    }
    return $url;
  }

}
