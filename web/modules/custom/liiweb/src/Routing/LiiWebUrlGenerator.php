<?php

namespace Drupal\liiweb\Routing;

use Drupal\Core\Routing\UrlGenerator;

/**
 * Overrides the core Drupal\Core\Routing\UrlGenerator.
 *
 * For AKN urls, the '@' character needs to be decoded.
 */
class LiiWebUrlGenerator extends UrlGenerator {

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
    $url = parent::generateFromRoute($name, $parameters, $options, $collect_bubbleable_metadata);

    $generatedUrl = $url->getGeneratedUrl();
    if (preg_match('/\/akn\/[a-zA-Z]+\/[0-9]+\/[0-9]+\/[a-zA-Z]+\%40[0-9]+\-[0-9]+\-[0-9]+/', $generatedUrl, $matches)) {
      $url->setGeneratedUrl(str_replace('%40', '@', $generatedUrl));
    }
    return $url;
  }

}
