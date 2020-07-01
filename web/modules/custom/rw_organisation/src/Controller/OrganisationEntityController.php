<?php

namespace Drupal\rw_organisation\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\rw_organisation\Entity\OrganisationEntityInterface;

/**
 * Class OrganisationEntityController.
 *
 *  Returns responses for Organisation routes.
 */
class OrganisationEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Organisation  revision.
   *
   * @param int $organisation_entity_revision
   *   The Organisation  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($organisation_entity_revision) {
    $organisation_entity = $this->entityManager()->getStorage('organisation_entity')->loadRevision($organisation_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('organisation_entity');

    return $view_builder->view($organisation_entity);
  }

  /**
   * Page title callback for a Organisation  revision.
   *
   * @param int $organisation_entity_revision
   *   The Organisation  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($organisation_entity_revision) {
    $organisation_entity = $this->entityManager()->getStorage('organisation_entity')->loadRevision($organisation_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $organisation_entity->label(), '%date' => format_date($organisation_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Organisation .
   *
   * @param \Drupal\rw_organisation\Entity\OrganisationEntityInterface $organisation_entity
   *   A Organisation  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(OrganisationEntityInterface $organisation_entity) {
    $account = $this->currentUser();
    $langcode = $organisation_entity->language()->getId();
    $langname = $organisation_entity->language()->getName();
    $languages = $organisation_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $organisation_entity_storage = $this->entityManager()->getStorage('organisation_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $organisation_entity->label()]) : $this->t('Revisions for %title', ['%title' => $organisation_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all organisation revisions") || $account->hasPermission('administer organisation entities')));
    $delete_permission = (($account->hasPermission("delete all organisation revisions") || $account->hasPermission('administer organisation entities')));

    $rows = [];

    $vids = $organisation_entity_storage->revisionIds($organisation_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\rw_organisation\OrganisationEntityInterface $revision */
      $revision = $organisation_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $organisation_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.organisation_entity.revision', ['organisation_entity' => $organisation_entity->id(), 'organisation_entity_revision' => $vid]));
        }
        else {
          $link = $organisation_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.organisation_entity.translation_revert', ['organisation_entity' => $organisation_entity->id(), 'organisation_entity_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.organisation_entity.revision_revert', ['organisation_entity' => $organisation_entity->id(), 'organisation_entity_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.organisation_entity.revision_delete', ['organisation_entity' => $organisation_entity->id(), 'organisation_entity_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['organisation_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
