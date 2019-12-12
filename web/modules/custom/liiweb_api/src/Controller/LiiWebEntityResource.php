<?php

namespace Drupal\liiweb_api\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\liiweb\LiiWebUtils;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class LiiWebEntityResource extends EntityResource {

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ResourceTypeRepositoryInterface $resource_type_repository, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, IncludeResolver $include_resolver, EntityAccessChecker $entity_access_checker, FieldResolver $field_resolver, SerializerInterface $serializer, TimeInterface $time, AccountInterface $user, LoggerChannelFactoryInterface $loggerChannelFactory, LiiWebUtils $liiWebUtils) {
    parent::__construct($entity_type_manager, $field_manager, $resource_type_repository, $renderer, $entity_repository, $include_resolver, $entity_access_checker, $field_resolver, $serializer, $time, $user);
    $this->logger = $loggerChannelFactory->get('liiweb_api');
    $this->liiWebUtils = $liiWebUtils;
  }

  /**
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $country
   * @param $year
   * @param $number
   * @param $langcode_year
   *
   * @return \Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(ResourceType $resource_type, Request $request, $country, $year, $number, $langcode_year) {
    $langcode_year = explode('@', $langcode_year);
    $date = $langcode_year[1];

    /** @var NodeInterface $parsed_entity */
    try {
      $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $status_code = $e instanceof HttpException ? $e->getStatusCode() : 400;
      return $this->getResourceResponseError($e->getMessage(), $status_code);
    }

    $create_revision = FALSE;
    // Try to load the node.
    $node = $this->liiWebUtils->getNodeFromFrbrUri($request->getRequestUri());
    if (empty($node)) {
      return $this->getResourceResponseError('The requested node does not exist.', 404);
    }

    $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());
    if (!empty($revision)) {
      return $this->getResourceResponseError('Revision already exists.', 400);
    }

    /** @var \Drupal\Core\Entity\TranslatableInterface $revision */
    $revision = $this->getRevisionWithPublicationDate($node, $date);
    // A revision with that publication date does not exist, and the langcode is different from the original language - invalid request
    if (empty($revision) && $node->language()->getId() != $parsed_entity->language()->getId()) {
      return $this->getResourceResponseError('Cannot create translations for revisions that do not exist. Please create a revision in the default language for that node with the requested date and try again.', 404);
    }

    // If we found the revision for that creation date, just create a translation for it.
    if (!empty($revision)) {
      $revision = $revision->addTranslation($parsed_entity->language()->getId());
      return $this->patchIndividual($resource_type, $revision, $request, $create_revision, 201);
    }

    // If we didn't find the revision for that creation date but the langcode is the same as the node,
    // just create a revision.
    return $this->patchIndividual($resource_type, $node, $request, TRUE, 201);
  }

  /**
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function patch(ResourceType $resource_type, Request $request) {
    $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());
    if (empty($revision)) {
      return $this->getResourceResponseError('The requested revision does not exist.', 404);
    }

    if (!$revision->access('update')) {
      throw new AccessDeniedHttpException();
    }

    return $this->patchIndividual($resource_type, $revision, $request);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param null $langcode_year
   *
   * @return array|\Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get(Request $request, $langcode_year = NULL) {
    /** @var NodeInterface $revision */
    if (empty($langcode_year)) {
      $revision = $this->liiWebUtils->getNodeFromFrbrUri($request->getRequestUri());
    }
    else {
      $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());
    }

    if (empty($revision)) {
      throw new NotFoundHttpException();
    }

    if ($request->headers->get('Accept') == 'application/json') {
      $response =  $this->getIndividual($revision, $request);
      $cacheability = (new CacheableMetadata())->addCacheContexts(['headers:Accept']);
      $response->addCacheableDependency($cacheability);
      return $response;
    }

    if (!$revision->access()) {
      throw new AccessDeniedHttpException();
    }

    $build = $this->entityTypeManager->getViewBuilder('node')->view($revision);
    $build['#cache']['contexts'][] = 'headers:Accept';
    return $build;
  }

  protected function getResourceResponseError($message, $status_code = 400) {
    $response = new ResourceResponse(new JsonApiDocumentTopLevel(new ErrorCollection([new HttpException($status_code, $message)]), new NullIncludedData(), new LinkCollection([])), $status_code);
    return $response;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param $date
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface|mixed|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRevisionWithPublicationDate(NodeInterface $node, $date) {
    $revisions = $this->getNodeRevisions($node);
    foreach ($revisions as $revision) {
      if ($revision->field_publication_date->value == $date) {
        return $revision;
      }
    }

    return NULL;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param null $langcode_year
   *
   * @return \Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete(Request $request, $langcode_year = NULL) {
    // Request to delete the node.
    if (empty($langcode_year)) {
      $node = $this->liiWebUtils->getNodeFromFrbrUri($request->getRequestUri());
      // Node not found.
      if (empty($node)) {
        return $this->getResourceResponseError('The requested node does not exist.', 404);
      }
      if (!$node->access('delete')) {
        throw new AccessDeniedHttpException();
      }
      $node->delete();
      return new ResourceResponse(NULL, 204);
    }

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());

    // Revision not found.
    if (empty($node)) {
      return $this->getResourceResponseError('The requested revision does not exist.', 404);
    }

    if (!$node->access('delete')) {
      throw new AccessDeniedHttpException();
    }

    // If it is not a revision for the default language, we can safely delete it.
    if (!$node->isDefaultTranslation()) {
      /** @var \Drupal\node\NodeInterface $defaultTranslation */
      $defaultTranslation = $nodeStorage->loadRevision($node->getRevisionId());
      $defaultTranslation->removeTranslation($node->language()->getId());
      $defaultTranslation->save();
      return new ResourceResponse(NULL, 204);
    }

    // If it is not the default revision, we can safely delete it.
    if (!$node->isDefaultRevision()) {
      $nodeStorage->deleteRevision($node->getRevisionId());
      return new ResourceResponse(NULL, 204);
    }

    $revisionIds = $nodeStorage->revisionIds($node);
    if (count($revisionIds) == 1) {
      return $this->getResourceResponseError('Cannot delete the only revision of a node. Delete the node instead.', 400);
    }

    // Before deleting the main revision, we need to set another revision as the main revision.
    $max = 0;
    $nextMainRevision = NULL;
    foreach ($revisionIds as $revisionId) {
      $revision = $nodeStorage->loadRevision($revisionId);
      if ($revision->field_publication_date->value > $max && $revisionId != $node->getRevisionId()) {
        $max = $revision->field_publication_date->value;
        $nextMainRevision = $revision;
      }
    }

    $nextMainRevision->isDefaultRevision(TRUE);
    $nextMainRevision->save();

    $node->isDefaultRevision(FALSE);
    $node->save();

    $nodeStorage->deleteRevision($node->getRevisionId());
    return new ResourceResponse(NULL, 204);
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|\Drupal\Core\Entity\RevisionableInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNodeRevisions(NodeInterface $node) {
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $revisionIds = $nodeStorage->revisionIds($node);
    return $nodeStorage->loadMultipleRevisions($revisionIds);
  }

  /**
   * {@inheritDoc}
   */
  public function patchIndividual(ResourceType $resource_type, EntityInterface $entity, Request $request, $create_revision = FALSE, $status_code = 200) {
    try {
      $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $status_code = $e instanceof HttpException ? $e->getStatusCode() : 400;
      return $this->getResourceResponseError($e->getMessage(), $status_code);
    }

    $body = Json::decode($request->getContent());
    $data = $body['data'];

    $data += ['attributes' => [], 'relationships' => []];
    $field_names = array_merge(array_keys($data['attributes']), array_keys($data['relationships']));

    array_reduce($field_names, function (EntityInterface $destination, $field_name) use ($resource_type, $parsed_entity) {
      $this->updateEntityField($resource_type, $parsed_entity, $destination, $field_name);
      return $destination;
    }, $entity);

    try {
      static::validate($entity, $field_names);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $status_code = $e instanceof HttpException ? $e->getStatusCode() : 400;
      return $this->getResourceResponseError($e->getMessage(), $status_code);
    }

    // Set revision data details for revisionable entities.
    if ($entity->getEntityType()->isRevisionable()) {
      /** @var $entity RevisionableInterface */
      $entity->setNewRevision($create_revision);
      if ($create_revision) {
        $default_revision = Node::load($entity->id());
        if ($entity->field_publication_date->value > $default_revision->field_publication_date->value) {
          $entity->isDefaultRevision(TRUE);
        }
        else {
          $entity->isDefaultRevision(FALSE);
        }
      }

      if ($entity instanceof RevisionLogInterface && $entity->isNewRevision()) {
        $entity->setRevisionUserId($this->user->id());
        $entity->setRevisionCreationTime($this->time->getRequestTime());
      }
    }

    $entity->save();
    $primary_data = new ResourceObjectData([ResourceObject::createFromEntity($resource_type, $entity)], 1);
    return $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data), $status_code);
  }

  /**
   * @inheritDoc
   */
  public function createIndividual(ResourceType $resource_type, Request $request) {
    $database = \Drupal::database();
    $transaction = $database->startTransaction();

    try {
      return parent::createIndividual($resource_type, $request);
    } catch (\Exception $e) {
      $transaction->rollback();
      $this->logger->error($e->getMessage());
      $status_code = $e instanceof HttpException ? $e->getStatusCode() : 400;
      return $this->getResourceResponseError($e->getMessage(), $status_code);
    }
  }

}
