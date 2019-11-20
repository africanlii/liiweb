<?php

namespace Drupal\liiweb_api\Normalizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Normalizes the top-level document according to the JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel
 */
class LiiWebJsonApiDocumentTopLevelNormalizer extends JsonApiDocumentTopLevelNormalizer {

  /**
   * @inheritDoc
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    if (empty($data['data']['type']) || ($data['data']['type'] != 'node--legislation' && $data['data']['type'] != 'paragraph--work_repeal')) {
      return FALSE;
    }

    return parent::supportsDenormalization($data, $type, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $resource_type = $context['resource_type'];

    // Validate a few common errors in document formatting.
    static::validateRequestBody($data, $resource_type);

    $normalized = [];

    if (!empty($data['data']['attributes'])) {
      $normalized = $data['data']['attributes'];
    }

    if (!empty($data['data']['id'])) {
      $uuid_key = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())->getKey('uuid');
      $normalized[$uuid_key] = $data['data']['id'];
    }

    if (!empty($data['data']['relationships'])) {
      // Turn all single object relationship data fields into an array of
      // objects.
      $relationships = array_map(function ($relationship) {
        if (isset($relationship['data']['type']) && isset($relationship['data']['id'])) {
          return ['data' => [$relationship['data']]];
        }
        else {
          return $relationship;
        }
      }, $data['data']['relationships']);

      // Get an array of ids for every relationship.
      $relationships = array_map(function ($relationship) {
        if (empty($relationship['data'])) {
          return [];
        }
        if (empty($relationship['data'][0]['id'])) {
          throw new BadRequestHttpException("No ID specified for related resource");
        }
        $id_list = array_column($relationship['data'], 'id');
        if (empty($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("No type specified for related resource");
        }
        if (!$resource_type = $this->resourceTypeRepository->getByTypeName($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }

        $entity_type_id = $resource_type->getEntityTypeId();
        try {
          $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
        }
        catch (PluginNotFoundException $e) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }
        // In order to maintain the order ($delta) of the relationships, we need
        // to load the entities and create a mapping between id and uuid.
        $uuid_key = $this->entityTypeManager
          ->getDefinition($entity_type_id)->getKey('uuid');
        $related_entities = array_values($entity_storage->loadByProperties([$uuid_key => $id_list]));
        $map = [];

        $revisionMapping = [];
        foreach ($id_list as $key => $id) {
          if ($id != 'virtual' || empty($relationship['data'][$key]['attributes'])) {
            continue;
          }

          /** @var \Drupal\jsonapi\Controller\EntityResource $service */
          $service = \Drupal::service('liiweb.entity_resource');
          /** @var ResourceType $relationEntityType */
          $relationResourceType = $this->resourceTypeRepository->getByTypeName($relationship['data'][$key]['type']);
          if (empty($relationResourceType)) {
            throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][$key]['type'] . "'");
          }

          $relationEntity = $this->getEntityForParameters($relationResourceType, $relationship['data'][$key]['attributes']);

          if (!empty($relationEntity)) {
            $map[$relationEntity->uuid()] = $relationEntity->id();
            $id_list[$key] = $relationEntity->uuid();
            if ($relationEntity instanceof Paragraph) {
              $revisionMapping[$relationEntity->uuid()] = $relationEntity->getRevisionId();
            }
            continue;
          }

          /** We allow creation of paragraphs and terms on the same request as the main entity */
          if ($relationResourceType->getEntityTypeId() == 'node') {
            throw new BadRequestHttpException(sprintf("Cannot find entity for requested parameters: %s", json_encode($relationship['data'][$key]['attributes'])));
          }

          $relationData = $relationship['data'][$key];
          unset($relationData['id']);
          $relationRequest = clone \Drupal::request();
          $relationRequest->initialize($relationRequest->query->all(),
            $relationRequest->request->all(),
            $relationRequest->attributes->all(),
            $relationRequest->cookies->all(),
            $relationRequest->files->all(),
            $relationRequest->server->all(),
            json_encode(['data' => $relationData]));

          $response = $service->createIndividual($relationResourceType, $relationRequest);

          if ($response->getResponseData()->getData()->count() != 1) {
            throw new BadRequestHttpException("There cannot be more than one individual created from one request! ");
          }

          $uuid = $response->getResponseData()->getData()->toArray()[0]->getId();
          $relationEntityStorage = $this->entityTypeManager
            ->getStorage($relationResourceType->getEntityTypeId());

          /** @var \Drupal\Core\Entity\Entity $relationEntity */
          $relationEntity = $relationEntityStorage->loadByProperties(['uuid' => $uuid]);
          if (empty($relationEntity)) {
            throw new BadRequestHttpException("Could not found relation entity!");
          }

          $relationEntity = reset($relationEntity);
          $map[$relationEntity->uuid()] = $relationEntity->id();
          $id_list[$key] = $relationEntity->uuid();
          if ($relationEntity instanceof Paragraph) {
            $revisionMapping[$relationEntity->uuid()] = $relationEntity->getRevisionId();
          }
        }

        foreach ($related_entities as $related_entity) {
          $map[$related_entity->uuid()] = $related_entity->id();
        }

        // $id_list has the correct order of uuids. We stitch this together with
        // $map which contains loaded entities, and then bring in the correct
        // meta values from the relationship, whose deltas match with $id_list.
        $canonical_ids = [];
        foreach ($id_list as $delta => $uuid) {
          if (!isset($map[$uuid])) {
            // @see \Drupal\jsonapi\Normalizer\EntityReferenceFieldNormalizer::normalize()
            if ($uuid === 'virtual') {
              continue;
            }
            throw new NotFoundHttpException(sprintf('The resource identified by `%s:%s` (given as a relationship item) could not be found.', $relationship['data'][$delta]['type'], $uuid));
          }
          $reference_item = [
            'target_id' => $map[$uuid],
          ];
          if (!empty($revisionMapping[$uuid])) {
            $reference_item['target_revision_id'] = $revisionMapping[$uuid];
          }
          if (isset($relationship['data'][$delta]['meta'])) {
            $reference_item += $relationship['data'][$delta]['meta'];
          }
          $canonical_ids[] = $reference_item;
        }

        return array_filter($canonical_ids);
      }, $relationships);

      // Add the relationship ids.
      $normalized = array_merge($normalized, $relationships);
    }
    // Override deserialization target class with the one in the ResourceType.
    $class = $context['resource_type']->getDeserializationTargetClass();

    return $this
      ->serializer
      ->denormalize($normalized, $class, $format, $context);
  }

  /**
   * @param \Drupal\jsonapi\ResourceType\ResourceType $relationResourceType
   * @param array $parameters
   *
   * @return mixed|void|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityForParameters(ResourceType $relationResourceType, array $parameters) {
    if ($relationResourceType->getEntityTypeId() == 'paragraph') {
      return;
    }

    $relationEntityStorage = $this->entityTypeManager
      ->getStorage($relationResourceType->getEntityTypeId());

    $query = $relationEntityStorage->getQuery();
    foreach ($parameters as $field => $value) {
      $query->condition($field, "$value%", "LIKE");
    }

    $ids = $query->execute();

    $entities = $relationEntityStorage->loadMultiple($ids);
    if (empty($entities)) {
      return NULL;
    }

    return reset($entities);
  }
}
