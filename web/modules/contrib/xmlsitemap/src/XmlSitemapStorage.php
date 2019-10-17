<?php

namespace Drupal\xmlsitemap;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * XmlSitemap storage service class.
 */
class XmlSitemapStorage extends ConfigEntityStorage {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ConfigEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, StateInterface $state) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    // Delete the auxiliar xmlsitemap data.
    foreach ($entities as $entity) {
      $this->state->delete('xmlsitemap.' . $entity->id());
    }

    parent::doDelete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = parent::doLoadMultiple($ids);

    // Load the auxiliar xmlsitemap data and attach it to the entity.
    foreach ($entities as $entity) {
      $settings = $this->state->get('xmlsitemap.' . $entity->id(), [
        'chunks' => NULL,
        'links' => NULL,
        'max_filesize' => NULL,
        'updated' => NULL,
      ]);

      foreach ($settings as $setting => $value) {
        $entity->{$setting} = $value;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    // Store the xmlsitemap auxiliar data.
    $this->state->set('xmlsitemap.' . $entity->id(), [
      'chunks' => $entity->getChunks(),
      'links' => $entity->getLinks(),
      'max_filesize' => $entity->getMaxFileSize(),
      'updated' => $entity->getUpdated(),
    ]);
    $is_new = parent::doSave($id, $entity);

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

}
