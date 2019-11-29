<?php

/**
 * @file NodeAccessTest.php
 */

namespace Drupal\Tests\liiweb\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Class LiiBrowserTestBase is the base class for AfricaLII tests.
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
abstract class LiiBrowserTestBase extends BrowserTestBase {

  protected static $permissions_api_legislation = [
    'create legislation content',
    'delete own legislation content', 'edit own legislation content',
    'manipulate legislation api'
    // 'create terms in legislation_tags'
  ];

  public function setUp() {
    parent::setUp();
  }

  /**
   * Create the 'legislation' content type with only the relevant fields.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createLegislationContentType() {
    $bundle = 'legislation';
    $field_name = 'field_created_by_api';
    $this->drupalCreateContentType([
      'type' => $bundle,
      'name' => 'Legislation',
    ]);
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'boolean',
      'langcode' => 'en',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Created by API',      'required' => FALSE,
      'translatable' => FALSE,
      'settings' => [
        'on_label' => 'Yes',
        'off_label' => 'No',
      ]
    ])->save();
  }
}
