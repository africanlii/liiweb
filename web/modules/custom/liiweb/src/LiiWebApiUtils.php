<?php

namespace Drupal\liiweb;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Class LiiWebApiUtils.
 */
class LiiWebApiUtils {

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
   * Constructs a new LiiWebApiUtils object.
   * {@inheritDoc}
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieve the node with a field_frbr_uri, without matching the last part of the url.
   *
   * @param $uri
   *   Example: /akn/za/1993/31/eng@1993-01-01
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\Entity\Node|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNodeFromFrbrUri($uri) {
    $uri = urldecode($uri);
    $base_uri = explode('/', $uri);
    array_pop($base_uri);
    $base_uri = implode('/', $base_uri);

    $node = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'legislation')
      ->condition('field_frbr_uri', "$base_uri/%", 'LIKE')
      ->execute();

    if (!empty($node)) {
      $node = reset($node);
    }

    return Node::load($node);
  }

  /**
   * Retrieve a revision with a frbr uri.
   *
   * @param $uri
   *   Example: /akn/za/1993/31/eng@1993-01-01
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRevisionFromFrbrUri($uri) {
    $uri = urldecode($uri);

    $result = $this->database->query("
    SELECT entity_id, revision_id, langcode
    FROM node_revision__field_frbr_uri
    WHERE field_frbr_uri_value = :uri
    ORDER BY revision_id DESC", [':uri' => $uri])->fetchAssoc();

    if (!empty($result['entity_id'])) {
      return $this->entityTypeManager->getStorage('node')->loadRevision($result['revision_id'])->getTranslation($result['langcode']);
    }

    return NULL;
  }

}
