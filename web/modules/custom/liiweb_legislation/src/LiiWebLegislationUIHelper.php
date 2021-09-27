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
   * Constructs a new LegislationUIHelper object.
   * {@inheritDoc}
   */
  public function __construct(DateFormatter $dateFormatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Format the banner for the newest version of the legislation.
   *
   * @param string $fromStr
   *   Start date in format YYYY-MM-DD.
   * @param integer $nid
   *   Expression node ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Formatted banner message.
   */
  public function formatBannerCurrentExpression($fromStr, $nid) {
    $default = new TranslatableMarkup('This is the latest version of this legislation.');
    if (empty($fromStr)) {
      return $default;
    }
    $from = \DateTime::createFromFormat('Y-m-d', $fromStr, new \DateTimeZone('UTC'));
    if (empty($from)) {
      return $default;
    }
    $from->setTime(0, 0);
    $fromFormatted = $this->dateFormatter->format($from->getTimestamp(), 'short_date');
    return new TranslatableMarkup('This is the latest version of this legislation commenced on <strong>@from</strong>.', ['@from' => $fromFormatted]);
  }

  /**
   * Generate banner message on older expressions of the legislation.
   *
   * @param string $fromStr
   *   Start date in format YYYY-MM-DD.
   * @param string $toStr
   *   End date in format YYYY-MM-DD.
   * @param integer $nid
   *   Expression node ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Formatted banner message.
   */
  public function formatBannerOlderExpression($fromStr, $toStr, $nid) {
    /** @var LiiWebUtils $liiWebUtils */
    $liiWebUtils = \Drupal::service('liiweb.utils');
    $default = new TranslatableMarkup('This is not the latest version of this legislation.');
    if (empty($fromStr) || empty($toStr)) {
      return $default;
    }
    $from = \DateTime::createFromFormat('Y-m-d', $fromStr, new \DateTimeZone('UTC'));
    $to = \DateTime::createFromFormat('Y-m-d', $toStr, new \DateTimeZone('UTC'));
    if (empty($from) || empty($to)) {
      return $default;
    }
    $from->setTime(0, 0);
    $to->setTime(0, 0);
    $fromFormatted = $this->dateFormatter->format($from->getTimestamp(), 'short_date');
    $toFormatted = $this->dateFormatter->format($to->getTimestamp(), 'short_date');
    try {
      $latestVid = $this->entityTypeManager->getStorage('node')->getLatestRevisionId($nid);
      $node = $this->entityTypeManager->getStorage('node')->loadRevision($latestVid);
      $json = $liiWebUtils->getLegislationJsonData($node);
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
          new TranslatableMarkup('Read the current version commenced on <strong>@from</strong>.', ['@from' => $latestRevisionFromFormatted]),
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

  /**
   * @param \Drupal\node\NodeInterface $repealWork
   *   Work that repealed the current legislation.
   * @param \DateTime $repealDate
   *   Date of repeal.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function formatBannerRepealedExpression($repealWork, $repealDate) {
    $default = new TranslatableMarkup('This legislation was repealed.');
    if (empty($repealDate)) {
      return $default;
    }
    $repealDateFormatted = $this->dateFormatter->format($repealDate->getTimestamp(), 'short_date');
    try {
      $link = Link::createFromRoute($repealWork->getTitle(),
        'entity.node.canonical',
        ['node' => $repealWork->id()],
        ['attributes' => ['title' => new TranslatableMarkup('Click to read this legislation')]]
      )->toString();
    } catch (\Exception $e) {
      $link = new TranslatableMarkup('Unknown');
    }
    return new TranslatableMarkup(
      'This legislation was repealed on <strong>@date</strong> by <h3 class="h6">@link</h3>.',
      [
        '@date' => $repealDateFormatted,
        '@link' => $link
      ]
    );
  }

}
