<?php

/**
 * @file
 * Defines a Drush8 commands that refreshes the S3 metadata cache.
 *
 * This file is only used by Drush8. Drush9 discovers its commands via tagged
 * service(s) in drush.services.yml. Also see classes in src/Commands.
 *
 * @deprecated It will removed in Drupal 8.6 because Drush8 commands are
 *             deprecated.
 */

use Drupal\Core\Site\Settings;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function s3fs_drush_command() {
  $items = [];

  $items['s3fs-refresh-cache'] = [
    'description' => dt('Refresh the S3 File System metadata cache.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => ['s3fs-rc'],
    'callback' => 'drush_s3fs_refresh_cache',
  ];

  $items['s3fs-copy-local'] = [
    'description' => dt('Copy local files from your public and/or private file system(s) into your S3 bucket, if s3fs is configured to take them over.'),
    'options' => [
      'scheme' => dt('Limit the process to an specific scheme. E.g. (public or private), all by default.'),
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => ['s3fs-cl'],
    'callback' => 'drush_s3fs_copy_local',
    'examples' => [
      'drush s3fs-copy-local' => dt('Copy local files from your public and/or private file system(s) into your S3 bucket.'),
      'drush s3fs-copy-local --scheme=public' => dt('Copy local files only from your public file system into your S3 bucket.'),
    ],
  ];

  return $items;
}

/**
 * Refreshes the file metadata cache.
 */
function drush_s3fs_refresh_cache() {
  $config = \Drupal::config('s3fs.settings')->get();

  if ($errors = \Drupal::service('s3fs')->validate($config)) {
    foreach ($errors as $error) {
      drush_set_error($error);
    }
    return drush_set_error(
      dt('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
    );
  }

  \Drupal::service('s3fs')->refreshCache($config);
}

/**
 * Copies all files from the local public/private filesystem folders into S3.
 *
 * @return void|int
 *   The drush action.
 */
function drush_s3fs_copy_local() {
  $scheme = drush_get_option('scheme', 'all');
  drush_log(dt('You are going to copy @scheme scheme(s).', ['@scheme' => $scheme]), LogLevel::OK);
  drush_log(dt('You should have read "Copy local files to S3" section in README.txt.'), LogLevel::WARNING);
  drush_print(dt('This command only is useful if you have or you are going to have enabled s3 for public/private in your setting.php'));

  if (!drush_confirm(dt('Are you sure?'))) {
    return drush_user_abort();
  }

  $config = \Drupal::config('s3fs.settings')->get();

  if ($errors = \Drupal::service('s3fs')->validate($config)) {
    foreach ($errors as $error) {
      drush_set_error($error);
    }
    return drush_set_error(
      dt('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
    );
  }

  if ($scheme == 'all' || $scheme == 'public') {
    drush_log(dt('Starting @scheme scheme', ['@scheme' => 'public']), LogLevel::OK);
    \Drupal::service('s3fs.file_migration_batch')->execute($config, 'public');
  }

  if ($scheme == 'all' || $scheme == 'private') {
    if (Settings::get('file_private_path')) {
      drush_log(dt('Starting @scheme scheme', ['@scheme' => 'private']), LogLevel::OK);
      \Drupal::service('s3fs.file_migration_batch')->execute($config, 'private');
    }
    else {
      drush_log(dt('Scheme @scheme is not properly configured, you must enable this scheme in your settings.php',
        ['@scheme' => 'private']), LogLevel::WARNING);
    }
  }
}
