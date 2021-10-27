<?php

namespace Drupal\liiweb;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Drupal\node\Entity\Node;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * Class LiiWebUtils.
 */
class LiiWebUtils {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LiiWebUtils object.
   * {@inheritDoc}
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieve a revision with a frbr uri.
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31/eng@1993-01-01
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRevisionFromFrbrUri($uri) {
    $uri = urldecode($uri);

    if (!$this->isAknUri($uri)) {
      return NULL;
    }

    $query = $this->database->select('node_revision__field_frbr_uri', 'n');
    $query->fields('n', ['entity_id', 'revision_id', 'langcode']);
    $query->condition('field_frbr_uri_value', $uri);
    $query->orderBy('revision_id', 'DESC');
    $result = $query->execute()->fetchAssoc();

    if (!empty($result['entity_id'])) {
      return $this->entityTypeManager->getStorage('node')->loadRevision($result['revision_id'])->getTranslation($result['langcode']);
    }

    return NULL;
  }

  /**
   * Retrieve the frbr uri of the latest expression of a work frbr uri.
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getLatestExpressionFromWorkFrbrUri($uri) {
    $uri = urldecode($uri);

    if (!$this->isAknUri($uri)) {
      return NULL;
    }

    // ensure the frbr uri ends with / to avoid incorrect partial matches
    $uri = $this->ensureEndingSlash($uri);

    $query = $this->database->select('node_revision__field_frbr_uri', 'n');
    $query->fields('n', ['field_frbr_uri_value']);
    $query->condition('field_frbr_uri_value', db_like($uri) . '%', 'LIKE');
    $query->orderBy('field_frbr_uri_value', 'DESC');
    $result = $query->execute()->fetchAssoc();

    if (!empty($result['field_frbr_uri_value'])) {
      return $result['field_frbr_uri_value'];
    }

    return NULL;
  }

  /**
   * Check if an URI is in AKN format.
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31/eng@1993-01-01
   *
   * @return bool
   */
  public function isAknUri($uri) {
    return strpos($uri, '/akn/') === 0;
  }

  /**
   * Ensure this URI ends with a /
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31
   *
   * @return string
   */
  public function ensureEndingSlash($uri) {
    if (substr($uri, -1) === '/') {
      return $uri;
    }
    return $uri . '/';
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param $langcode
   *
   * @return string|null
   */
  public function getLatestFrbrUriForNode(\Drupal\node\NodeInterface $node, $langcode) {
    if ($node->bundle() != 'legislation') {
      return NULL;
    }

    // Make sure we have the default revision.
    $node = Node::load($node->id());

    if ($node->language()->getId() == $langcode) {
      return $node->field_frbr_uri->value;
    }

    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
      return $node->field_frbr_uri->value;
    }

    return NULL;
  }

  /**
   * @param \Exception $e
   *   Underlying exception from JSON API.
   *
   * @throws \Exception
   * @return string
   *   Former exception message plus underlying error messages.
   */
  public function prepareAPIExceptionMessage($e) {
    $messages[] = $e->getMessage();
    if ($e instanceof UnprocessableHttpEntityException) {
      $violations = $e->getViolations();
      foreach($violations->getIterator() as $violation) {
        // Sometimes these contain useful information about the root error
        // $violation->getPropertyPath();
        // $violation->getInvalidValue();
        $messages[] = (string)$violation->getMessage();
      }
    }
    return implode(" ", $messages);
   * Extract JSON information from the given Legislation revision.
   *
   * @param \Drupal\node\Entity\Node|\Drupal\Core\Entity\EntityInterface $node
   *
   * @return mixed|null
   *   Decoded JSON data.
   */
  public function getLegislationJsonData($node) {
    if ($node instanceof \Drupal\node\NodeInterface && $node->getType() == 'legislation') {
      if (!empty($node->field_raw_json->value)) {
        return json_decode($node->field_raw_json->value);
      }
    }
    return NULL;
  }


  /**
   * Load next chronological expression for a given legislation and current revision.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Legislation node
   * @param integer $referenceRevisionId
   *   Referenced revision
   *
   * @return \Drupal\node\NodeInterface
   */
  function getNextExpression($node, $referenceRevisionId) {
    try {
      $revisions = $this->entityTypeManager
        ->getStorage('node')
        ->revisionIds($node);
    } catch (\Exception $e) {}
    sort($revisions);
    $nextRevisionId = NULL;
    foreach($revisions as $revisionId) {
      // Next incremental node revision ID is always the following expression chronologically
      if ($revisionId > $referenceRevisionId) {
        $nextRevisionId = $revisionId;
        break;
      }
    }
    if ($nextRevisionId) {
      try {
        return $this->entityTypeManager
          ->getStorage('node')
          ->loadRevision($nextRevisionId);
      } catch (\Exception $e) {}
    }
    return NULL;
>>>>>>> master
  }
}
