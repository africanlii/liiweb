<?php

namespace Drupal\liiweb_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\facets\Exception\Exception;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LiiWebEntityResource extends EntityResource {

  const LANGCODE_MAPPING = [
    'fra' => 'fr',
    'eng' => 'en',
  ];

  protected function getRevisionFromFrbrUri($uri) {
    $this->getNodeFromFrbrUri($uri);

    $result = \Drupal::database()->query("
    SELECT entity_id, revision_id, langcode
    FROM node_revision__field_frbr_uri
    WHERE field_frbr_uri_value = :uri
    ORDER BY revision_id DESC", [':uri' => $uri])->fetchAssoc();

    if (!empty($result['entity_id'])) {
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($result['revision_id'])->getTranslation($result['langcode']);
    }

    return NULL;
  }

  protected function getNodeFromFrbrUri($uri) {
    $base_uri = explode('/', $uri);
    array_pop($base_uri);
    $base_uri = implode('/', $base_uri);

    $node = \Drupal::entityQuery('node')
      ->condition('type', 'legislation')
      ->condition('field_frbr_uri', "$base_uri/%", 'LIKE')
      ->execute();

    if (!empty($node)) {
      $node = reset($node);
    }

    return Node::load($node);
  }

  public function apiCall(ResourceType $resource_type, Request $request, $country, $year, $number, $langcode_year = NULL) {
    $request_method = $request->getMethod();

    if (in_array($request_method, ['POST', 'PATCH'])) {
      /** @var NodeInterface $parsed_entity */
      $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);
    }

    if ($request_method == 'DELETE') {
      return $this->delete($request, $langcode_year);
    }

    if (!empty($langcode_year)) {
      $langcode_year = explode('@', $langcode_year);
      $langcode = $langcode_year[0];
      $date = $langcode_year[1];
    }

    if ($request_method == 'POST') {
      $create_revision = FALSE;
      // Try to load the node.
      $node = $this->getNodeFromFrbrUri($request->getRequestUri());
      if (empty($node)) {
        return $this->getResourceResponseError('The requested node does not exist.', 404);
      }

      $revision = $this->getRevisionFromFrbrUri($request->getRequestUri());
      if (!empty($revision)) {
        return $this->getResourceResponseError('Revision already exists.', 400);
      }

      /** @var \Drupal\Core\Entity\TranslatableInterface $revision */
      $revision = $this->getRevisionWithPublicationDate($node, $date);
      // A revision with that creation date does not exist, and the langcode is different from the original language - invalid request
      if (empty($revision) && $node->language()->getId() != $parsed_entity->language()->getId()) {
        return $this->getResourceResponseError('Cannot create translations for revisions that do not exist. Please create a revision in the default language for that node with the requested date and try again.', 404);
      }

      // If we found the revision for that creation date, just create a translation for it.
      if (!empty($revision)) {
        $revision = $revision->addTranslation($parsed_entity->language()->getId());
        return $this->patchIndividual($resource_type, $revision, $request, $create_revision);
      }
      // If we didn't find the revision for that creation date but the langcode is the same as the node,
      // just create a revision.
      else {
        $revision = $node;
        return $this->patchIndividual($resource_type, $revision, $request, TRUE);
      }
    }

    if ($request_method == 'PATCH') {
      $revision = $this->getRevisionFromFrbrUri($request->getRequestUri());
      if (empty($revision)) {
        return $this->getResourceResponseError('The requested revision does not exist.', 404);
      }

      return $this->patchIndividual($resource_type, $revision, $request);
    }

    return $this->getResourceResponseError('Method not accepted', 400);
  }

  protected function getResourceResponseError($message, $status_code) {
    $response = new ResourceResponse(new JsonApiDocumentTopLevel(new ErrorCollection([new HttpException($status_code, $message)]), new NullIncludedData(), new LinkCollection([])), $status_code);
    return $response;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param $date
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface|null
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

  public function delete(Request $request, $langcode_year = NULL) {
    // Request to delete the node.
    if (empty($langcode_year)) {
      $node = $this->getNodeFromFrbrUri($request->getRequestUri());
      // Node not found.
      if (empty($node)) {
        return $this->getResourceResponseError('The requested node does not exist.', 404);
      }
      $node->delete();
      return new ResourceResponse(NULL, 204);
    }

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    /** @var RevisionableInterface $node */
    $node = $this->getRevisionFromFrbrUri($request->getRequestUri());

    // Revision not found.
    if (empty($node)) {
      return $this->getResourceResponseError('The requested revision does not exist.', 404);
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
  public function patchIndividual(ResourceType $resource_type, EntityInterface $entity, Request $request, $create_revision = FALSE) {
    $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);

    $body = Json::decode($request->getContent());
    $data = $body['data'];

    $data += ['attributes' => [], 'relationships' => []];
    $field_names = array_merge(array_keys($data['attributes']), array_keys($data['relationships']));

    array_reduce($field_names, function (EntityInterface $destination, $field_name) use ($resource_type, $parsed_entity) {
      $this->updateEntityField($resource_type, $parsed_entity, $destination, $field_name);
      return $destination;
    }, $entity);

    static::validate($entity, $field_names);

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
    return $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data));
  }

}
