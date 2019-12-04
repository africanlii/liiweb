<?php

namespace Drupal\liiweb_api\EventSubscriber;

use Drupal\Core\Url;
use Drupal\liiweb_api\Controller\LiiWebEntityResource;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class LiiWebApiEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\liiweb_api\Controller\LiiWebEntityResource
   */
  protected $liiWebEntityResource;

  public function __construct(LiiWebEntityResource $liiWebEntityResource) {
    $this->liiWebEntityResource = $liiWebEntityResource;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return([
      KernelEvents::REQUEST => [
        ['serializeEntity'],
      ],
    ]);
  }

  /**
   * Redirect requests for nodes, for taxonomy terms, sets globals for assess.
   */
  public function serializeEntity(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->attributes->get('_route') !== 'entity.node.canonical'
      || $request->headers->get('Accept') !== 'application/json') {
      return;
    }

    /** @var \Drupal\node\Entity\Node $node */
    $node = $request->attributes->get('node');
    if ($node->getType() !== 'legislation') {
      return;
    }
    if (!$node->access()) {
      throw new AccessDeniedHttpException();
    }

    $response = $this->liiWebEntityResource->getIndividual($node, $request);
    $event->setResponse($response);
  }

}
