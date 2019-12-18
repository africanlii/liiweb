<?php

namespace Drupal\s3fs\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simpletest\WebTestBase;


/**
 * Tests s3fs configuration form.
 *
 * @group s3fs
 */
class S3fsConfigFormTest extends WebTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['s3fs'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and login user.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer s3fs',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test the S3fs config form.
   */
  public function testS3fsConfigurationForm() {
    $edit['bucket'] = 's3fs-testing-bucket';
    $edit['region'] = 'us-east-1';
    $edit['use_cname'] = 1;
    $edit['domain'] = 'domaincheck.com';
    $edit['use_path_style_endpoint'] = 1;
    $edit['encryption'] = 'AES256';
    $edit['use_https'] = 1;
    $edit['root_folder'] = 'rootfoldercheck';
    $edit['presigned_urls'] = '60|private_files/*';
    $edit['saveas'] = 'video/*';
    $edit['torrents'] = 'big_files/*';
    $this->drupalPostForm('admin/config/media/s3fs', $edit, $this->t('Save configuration'));
    $this->assertText($this->t('The configuration options have been saved.'), $this->t('Saved configuration'));
  }

}
