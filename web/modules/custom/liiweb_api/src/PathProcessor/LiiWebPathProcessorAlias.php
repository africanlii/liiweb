<?php

namespace Drupal\liiweb_api\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\liiweb\LiiWebUtils;
use Symfony\Component\HttpFoundation\Request;

class LiiWebPathProcessorAlias extends PathProcessorAlias {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  /**
   * LiiWebPathProcessorAlias constructor.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   * @param \Drupal\liiweb\LiiWebUtils $liiWebUtils
   */
  public function __construct(AliasManagerInterface $alias_manager, LiiWebUtils $liiWebUtils) {
    parent::__construct($alias_manager);
    $this->liiWebUtils = $liiWebUtils;
  }

  /**
   * We need to override the PathProcessorAlias method so that API Calls don't get routed to /node/nid because of URL Aliasing.
   *
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($this->liiWebUtils->isAknUri($request->getRequestUri()) && ($request->getMethod() !== 'GET' || $request->headers->get('Accept') === 'application/json')) {
      return $path;
    }

    return parent::processInbound($path, $request);
  }

}