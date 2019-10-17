<?php

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test status messages functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsStatusMessagesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'google_analytics_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if status messages tracking is properly added to the page.
   */
  public function testGoogleAnalyticsStatusMessages() {
    $ua_code = 'UA-123456-4';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable logging of errors only.
    $this->config('google_analytics.settings')->set('track.messages', ['error' => 'error'])->save();

    $this->drupalPostForm('user/login', [], t('Log in'));
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Username field is required."});', '[testGoogleAnalyticsStatusMessages]: Event message "Username field is required." is shown.');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Password field is required."});', '[testGoogleAnalyticsStatusMessages]: Event message "Password field is required." is shown.');

    // Testing this drupal_set_message() requires an extra test module.
    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertNoRaw('gtag("event", "Status message", {"event_category":"Messages","event_label":"Example status message."});', '[testGoogleAnalyticsStatusMessages]: Example status message is not enabled for tracking.');
    $this->assertNoRaw('gtag("event", "Warning message", {"event_category":"Messages","event_label":"Example warning message."});', '[testGoogleAnalyticsStatusMessages]: Example warning message is not enabled for tracking.');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message."});', '[testGoogleAnalyticsStatusMessages]: Example error message is shown.');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message with html tags and link."});', '[testGoogleAnalyticsStatusMessages]: HTML has been stripped successful from Example error message with html tags and link.');

    // Enable logging of status, warnings and errors.
    $this->config('google_analytics.settings')->set('track.messages', [
      'status' => 'status',
      'warning' => 'warning',
      'error' => 'error',
    ])->save();

    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertRaw('gtag("event", "Status message", {"event_category":"Messages","event_label":"Example status message."});', '[testGoogleAnalyticsStatusMessages]: Example status message is enabled for tracking.');
    $this->assertRaw('gtag("event", "Warning message", {"event_category":"Messages","event_label":"Example warning message."});', '[testGoogleAnalyticsStatusMessages]: Example warning message is enabled for tracking.');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message."});', '[testGoogleAnalyticsStatusMessages]: Example error message is shown.');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message with html tags and link."});', '[testGoogleAnalyticsStatusMessages]: HTML has been stripped successful from Example error message with html tags and link.');
  }

}
