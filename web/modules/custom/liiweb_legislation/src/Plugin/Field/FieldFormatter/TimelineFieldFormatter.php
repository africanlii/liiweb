<?php

namespace Drupal\liiweb_legislation\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'timeline_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "timeline_field_formatter",
 *   label = @Translation("Timeline field formatter"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class TimelineFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#attached' => [
          'library' => ['liiweb_legislation/timeline-graph'],
          'drupalSettings' => [
            'liiweb_legislation' => [
              'timeline' => [
                'data' => $this->viewValue($item)
              ]
            ]
          ]
        ],
        '#theme' => 'lifecycle_timeline',
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $value = json_decode($item->getValue()['value'], TRUE);
    $historic_events = [];
    $value = array_reverse($value);
    foreach($value as $event) {
      foreach($event['events'] as $event_array){
        if(isset($event_array['amending_title'])) {
          $title = $event_array['amending_title'];
        }
        else {
          $title = '';
        }
        if($event['expression_frbr_uri']) {
          $description = "<br><b><a href='".$event['expression_frbr_uri']."'>Read More</a></b>";
        }
        else {
          $description = "";
        }
        $historic_events[] = [
          'name' => date('Y-m-d', strtotime($event['date'])).': '.ucfirst($event_array['event']),
          'description' => $title.$description,
          'x' =>  strtotime($event['date']) * 1000,
          'y' => 1,
          'color' => '#4a4a4a'
        ];
      }
    }
    $historic_events[] = [
      'name' => date('Y-m-d'),
      'description' => 'Today',
      'x' =>  time() * 1000,
      'y' => 1,
      'color' => '#5a9d1c'
    ];

    return $historic_events;

  }

}
