<?php

namespace Drupal\liiweb_frontpage\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the liiweb_frontpage module.
 */
class FrontpageControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "liiweb_frontpage FrontpageController's controller functionality",
      'description' => 'Test Unit for module liiweb_frontpage and controller FrontpageController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests liiweb_frontpage functionality.
   */
  public function testFrontpageController() {
    // Check that the basic functions of module liiweb_frontpage.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
