<?php

namespace Drupal\rw_organisation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Organisation type entity.
 *
 * @ConfigEntityType(
 *   id = "organisation_entity_type",
 *   label = @Translation("Organisation type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\rw_organisation\OrganisationEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rw_organisation\Form\OrganisationEntityTypeForm",
 *       "edit" = "Drupal\rw_organisation\Form\OrganisationEntityTypeForm",
 *       "delete" = "Drupal\rw_organisation\Form\OrganisationEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\rw_organisation\OrganisationEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "organisation_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "organisation_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/organisations/organisation_entity_type/{organisation_entity_type}",
 *     "add-form" = "/admin/content/organisations/organisation_entity_type/add",
 *     "edit-form" = "/admin/content/organisations/organisation_entity_type/{organisation_entity_type}/edit",
 *     "delete-form" = "/admin/content/organisations/organisation_entity_type/{organisation_entity_type}/delete",
 *     "collection" = "/admin/content/organisations/organisation_entity_type"
 *   }
 * )
 */
class OrganisationEntityType extends ConfigEntityBundleBase implements OrganisationEntityTypeInterface {

  /**
   * The Organisation type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Organisation type label.
   *
   * @var string
   */
  protected $label;

}
