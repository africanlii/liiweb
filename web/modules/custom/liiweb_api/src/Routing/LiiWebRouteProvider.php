<?php

namespace Drupal\liiweb_api\Routing;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\StateInterface;
use Drupal\liiweb\LiiWebUtils;
use Symfony\Component\HttpFoundation\Request;

class LiiWebRouteProvider extends RouteProvider {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  public function __construct(Connection $connection, StateInterface $state, CurrentPathStack $current_path, CacheBackendInterface $cache_backend, InboundPathProcessorInterface $path_processor, CacheTagsInvalidatorInterface $cache_tag_invalidator, LiiWebUtils $liiWebUtils, $table = 'router', LanguageManagerInterface $language_manager = NULL) {
    parent::__construct($connection, $state, $current_path, $cache_backend, $path_processor, $cache_tag_invalidator, $table, $language_manager);
    $this->liiWebUtils = $liiWebUtils;
  }

  /**
   * Override this function so that AKN URLs dont get cached.
   *
   * The reason we don't want AKN URLs to be cached is that,
   * depending on the method, they are routed somewhere else.
   *
   * {@inheritdoc}
   */
  public function getRouteCollectionForRequest(Request $request) {
    // Cache both the system path as well as route parameters and matching
    // routes.
    $cid = $this->getRouteCollectionCacheId($request);
    if ($cached = $this->cache->get($cid)) {
      $this->currentPath->setPath($cached->data['path'], $request);
      $request->query->replace($cached->data['query']);
      return $cached->data['routes'];
    }
    else {
      // Just trim on the right side.
      $path = $request->getPathInfo();
      $path = $path === '/' ? $path : rtrim($request->getPathInfo(), '/');
      $originalPath = $path;
      $path = $this->pathProcessor->processInbound($path, $request);
      $this->currentPath->setPath($path, $request);
      // Incoming path processors may also set query parameters.
      $query_parameters = $request->query->all();
      $routes = $this->getRoutesByPath(rtrim($path, '/'));
      if (!$this->liiWebUtils->isAknUri($originalPath)) {
        $cache_value = [
          'path' => $path,
          'query' => $query_parameters,
          'routes' => $routes,
        ];
        $this->cache->set($cid, $cache_value, CacheBackendInterface::CACHE_PERMANENT, ['route_match']);
      }
      return $routes;
    }
  }

}