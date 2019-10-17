<?php

namespace Drupal\google_analytics\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Test custom url functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsCustomUrls extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if user password page urls are overridden.
   */
  public function testGoogleAnalyticsCustomUrls() {
    $base_path = base_path();
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')
      ->set('account', $ua_code)
      ->set('privacy.anonymizeip', 0)
      ->set('track.displayfeatures', 1)
      ->save();

    $this->drupalGet('user/password', ['query' => ['name' => 'foo']]);
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":"' . $base_path . 'user/password"});');

    $this->drupalGet('user/password', ['query' => ['name' => 'foo@example.com']]);
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":"' . $base_path . 'user/password"});');

    $this->drupalGet('user/password');
    $this->assertNoRaw('"page_path":"' . $base_path . 'user/password"});', '[testGoogleAnalyticsCustomUrls]: Custom url not set.');

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertResponse(403);
    $this->assertRaw($base_path . '403.html', '[testGoogleAnalyticsCustomUrls]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertResponse(404);
    $this->assertRaw($base_path . '404.html', '[testGoogleAnalyticsCustomUrls]: 404 Not Found tracking code shown on non-existent page.');
  }

}
