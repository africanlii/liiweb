<?php

namespace Drupal\liiweb_search\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LiiWebSearchEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new RouteMatchInterface.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['facetRedirect', 30];
    return $events;
  }

  /**
   * Redirect requests for search facets!
   */
  public function facetRedirect(GetResponseEvent $event) {
    $request = $event->getRequest();
    $route_name = $this->currentRouteMatch->getRouteName();

    if ($route_name == 'view.liiweb_search.page_search') {
      if (!empty($request->query->get('type'))) {
        $full_text_search = $request->query->get('search_api_fulltext');
        $type = $request->query->get('type');
        if ($type == "All") {
          $url = Url::fromRoute('view.liiweb_search.page_search')
            ->setRouteParameters([
              'search_api_fulltext' => $full_text_search,
            ]);
        }
        else {
          $url = Url::fromRoute('view.liiweb_search.page_search')
            ->setRouteParameters([
              'search_api_fulltext' => $full_text_search,
              'f[0]' => 'content_type:' . $type,
            ]);
        }
        $response = new TrustedRedirectResponse($url->toString(), 301);
        $event->setResponse($response);
      }
    }
  }

}
