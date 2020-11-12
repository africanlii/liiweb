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
   *
   * @return \Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(ResourceType $resource_type, Request $request) {
    /** @var NodeInterface $parsed_entity */
    try {
      $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $status_code = $e instanceof HttpException ? $e->getStatusCode() : 400;
      return $this->getResourceResponseError($e->getMessage(), $status_code);
    }

    $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());
    if (empty($revision)) {
      return $this->getResourceResponseError("No revision was found with the frbr uri " . $request->getRequestUri(), 404);
    }

    // The langcodes are different between the original revision and the revision in the payload - create a translation
    if ($revision->language()->getId() != $parsed_entity->language()->getId()) {
      if ($revision->hasTranslation($parsed_entity->language()->getId())) {
        return $this->getResourceResponseError('The original revision already has a translation for langcode ' . $parsed_entity->language()->getId(), 400);
      }

      $revision = $revision->addTranslation($parsed_entity->language()->getId());
      return $this->patchIndividual($resource_type, $revision, $request, FALSE, 201);
    }

    // The langcodes are identical - create a new revision
    return $this->patchIndividual($resource_type, $revision, $request, TRUE, 201);
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
   *
   * @return array|\Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get(Request $request) {
    /** @var NodeInterface $revision */
    $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getPathInfo());

    if (empty($revision)) {
      return $this->getResourceResponseError("No revision was found with the frbr uri " . $request->getPathInfo(), 404);
    }

    if ($request->headers->get('Accept') == 'application/vnd.api+json') {
      $revision->addCacheContexts(['url']);
      $response =  $this->getIndividual($revision, $request);
      $cacheability = (new CacheableMetadata())->addCacheContexts(['headers:Accept', 'url']);
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\jsonapi\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete(Request $request) {
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $revision */
    $revision = $this->liiWebUtils->getRevisionFromFrbrUri($request->getRequestUri());

    // Revision not found.
    if (empty($revision)) {
      return $this->getResourceResponseError('The requested revision does not exist.', 404);
    }

    if (!$revision->access('delete')) {
      throw new AccessDeniedHttpException();
    }

    // If it is not a revision for the default language, we can safely delete it.
    if (!$revision->isDefaultTranslation()) {
      /** @var \Drupal\node\NodeInterface $defaultTranslation */
      $defaultTranslation = $nodeStorage->loadRevision($revision->getRevisionId());
      $defaultTranslation->removeTranslation($revision->language()->getId());
      $defaultTranslation->save();
      return new ResourceResponse(NULL, 204);
    }

    // If it is not the default revision, we can safely delete it.
    if (!$revision->isDefaultRevision()) {
      $nodeStorage->deleteRevision($revision->getRevisionId());
      return new ResourceResponse(NULL, 204);
    }

    $revisionIds = $nodeStorage->revisionIds($revision);
    // If this is the only revision, delete the node.
    if (count($revisionIds) == 1) {
      $revision->delete();
      return new ResourceResponse(NULL, 204);
    }

    // Before deleting the main revision, we need to set another revision as the main revision.
    $max = 0;
    $nextMainRevision = NULL;
    foreach ($revisionIds as $revisionId) {
      $otherRevision = $nodeStorage->loadRevision($revisionId);
      if ($otherRevision->field_expression_date->value > $max && $revisionId != $revision->getRevisionId()) {
        $max = $otherRevision->field_expression_date->value;
        $nextMainRevision = $otherRevision;
      }
    }

    $nextMainRevision->isDefaultRevision(TRUE);
    $nextMainRevision->save();

    $revision->isDefaultRevision(FALSE);
    $revision->save();

    $nodeStorage->deleteRevision($revision->getRevisionId());
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
        if ($entity->field_expression_date->value > $default_revision->field_expression_date->value) {
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
