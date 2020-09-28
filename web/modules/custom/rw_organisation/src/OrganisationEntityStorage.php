<?php

namespace Drupal\rw_organisation;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\rw_organisation\Entity\OrganisationEntityInterface;

/**
 * Defines the storage handler class for Organisation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Organisation entities.
 *
 * @ingroup rw_organisation
 */
class OrganisationEntityStorage extends SqlContentEntityStorage implements OrganisationEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OrganisationEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {organisation_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {organisation_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OrganisationEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {organisation_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('organisation_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
