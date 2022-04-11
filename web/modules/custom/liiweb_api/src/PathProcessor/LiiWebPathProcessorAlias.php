<?php

namespace Drupal\liiweb_api\PathProcessor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\liiweb\LiiWebUtils;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

class LiiWebPathProcessorAlias extends AliasPathProcessor {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * LiiWebPathProcessorAlias constructor.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   * @param \Drupal\liiweb\LiiWebUtils $liiWebUtils
   */
  public function __construct(AliasManagerInterface $alias_manager, LiiWebUtils $liiWebUtils, LanguageManagerInterface $languageManager) {
    parent::__construct($alias_manager);
    $this->liiWebUtils = $liiWebUtils;
    $this->languageManager = $languageManager;
  }

  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    preg_match('/^\/node\/([0-9]+)$/', $path, $matches);

    if (!empty($matches[1])) {
      $langcode = isset($options['language']) ? $options['language']->getId() : $this->languageManager->getCurrentLanguage()->getId();
      $nid = $matches[1];
      $alias = $this->liiWebUtils->getLatestFrbrUriForNode(Node::load($nid), $langcode);
      if (!empty($alias)) {
        return $alias;
      }
    }

    return parent::processOutbound($path, $options, $request, $bubbleable_metadata);
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

    $node = $this->liiWebUtils->getRevisionFromFrbrUri($path);
    if ($node instanceof NodeInterface && $node->isDefaultRevision()) {
      return "/node/{$node->id()}";
    }

    return parent::processInbound($path, $request);
  }

}
