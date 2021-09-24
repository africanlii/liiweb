<?php

namespace Drupal\liiweb_legislation;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\liiweb\LiiWebUtils;

class LiiWebLegislationUIHelper {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Datetime\DateFormatterInterface definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\liiweb\LiiWebUtils
   */
  protected $liiWebUtils;

  /**
   * Constructs a new LegislationUIHelper object.
   * {@inheritDoc}
   */
  public function __construct(DateFormatter $dateFormatter, EntityTypeManagerInterface $entity_type_manager, LiiWebUtils $liiWebUtils) {
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->liiWebUtils = $liiWebUtils;
  }

  public function formatMessageOlderExpression($fromStr, $toStr, $nid) {
    if (empty($fromStr) || empty($toStr)) {
      return '';
    }
    $from = \DateTime::createFromFormat('Y-m-d', $fromStr, new \DateTimeZone('UTC'));
    $to = \DateTime::createFromFormat('Y-m-d', $toStr, new \DateTimeZone('UTC'));
    if (empty($from) || empty($to)) {
      return '';
    }
    $from->setTime(0, 0);
    $to->setTime(0, 0);
    $fromFormatted = $this->dateFormatter->format($from->getTimestamp(), 'short_date');
    $toFormatted = $this->dateFormatter->format($to->getTimestamp(), 'short_date');
    try {
      $latestVid = $this->entityTypeManager->getStorage('node')->getLatestRevisionId($nid);
      $node = $this->entityTypeManager->getStorage('node')->loadRevision($latestVid);
      $json = $this->liiWebUtils->getLegislationJsonData($node);
      // Get the date of the newest revision.
      $latestRevisionFromFormatted = NULL;
      if ($latestRevisionFromStr = $json->expression_date) {
        if ($latestRevisionFrom = \DateTime::createFromFormat('Y-m-d', $latestRevisionFromStr, new \DateTimeZone('UTC'))) {
          $latestRevisionFrom->setTime(0, 0);
          $latestRevisionFromFormatted = $this->dateFormatter->format($latestRevisionFrom->getTimestamp(), 'short_date');
        }
      }
      if ($latestRevisionFromFormatted) {
        $link = Link::createFromRoute(
          new TranslatableMarkup('Read the version currently in force from <strong>@from</strong>.', ['@from' => $latestRevisionFromFormatted]),
          'entity.node.canonical', ['node' => $nid]
        )->toString();
      }
      else {
        $link = Link::createFromRoute(
          new TranslatableMarkup('Read the version currently in force.'),
          'entity.node.canonical', ['node' => $nid]
        )->toString();
      }
    } catch (\Exception $e) {
      $link = new TranslatableMarkup('Unknown');;
    }
    return new TranslatableMarkup(
      'This is the version of this legislation as it was from <strong>@from</strong> to <strong>@to</strong>. <h3 class="h6">@link</h3>',
      ['@from' => $fromFormatted, '@to' => $toFormatted, '@link' => $link]
    );
  }

}
