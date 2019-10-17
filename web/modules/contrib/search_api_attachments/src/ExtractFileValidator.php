<?php

namespace Drupal\search_api_attachments;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Drupal\Component\Utility\Bytes;

/**
 * Validator class for attachment indexing.
 */
class ExtractFileValidator {
  /**
   * The default excluded file extension list.
   */
  const DEFAULT_EXCLUDED_EXTENSIONS = 'aif art avi bmp gif ico mov oga ogv png psd ra ram rgb flv';

  /**
   * The route match.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * Constructs a new ExtractFileValidator class.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   Mime type guesser service.
   */
  public function __construct(MimeTypeGuesserInterface $mimeTypeGuesser) {
    $this->mimeTypeGuesser = $mimeTypeGuesser;
  }

  /**
   * Get a corresponding array of excluded mime types.
   *
   * Obtained from a space separated string of file extensions.
   *
   * @param string $extensions
   *   If it's not null, the return will correspond to the extensions.
   *   If it is null,the return will correspond to the default excluded
   *   extensions.
   * @param string $excluded_extensions
   *   Exclude extensions string.
   *
   * @return array
   *   Array or mimes.
   */
  public function getExcludedMimes($extensions = NULL, $excluded_extensions = NULL) {
    if (!$extensions && !empty($excluded_extensions)) {
      $excluded_mimes_string = $excluded_extensions;
      $excluded_mimes = explode(' ', $excluded_mimes_string);
    }
    else {
      if (!$extensions) {
        $extensions = explode(' ', self::DEFAULT_EXCLUDED_EXTENSIONS);
      }
      $excluded_mimes = [];
      foreach ($extensions as $extension) {
        $excluded_mimes[] = $this->mimeTypeGuesser->guess('dummy.' . $extension);
      }
    }
    // Ensure we get an array of unique mime values because many extension can
    // map the the same mime type.
    return array_unique($excluded_mimes);
  }

  /**
   * Exclude files that exceed configured max size.
   *
   * @param object $file
   *   File object.
   * @param int $max_filesize
   *   Max allowed file size.
   *
   * @return bool
   *   TRUE if the file size does not exceed configured max size.
   */
  public function isFileSizeAllowed($file, $max_filesize = 0) {
    if (!empty($max_filesize)) {
      $configured_size = $max_filesize;
      if ($configured_size == '0') {
        return TRUE;
      }
      else {
        $file_size_bytes = $file->getSize();
        $configured_size_bytes = Bytes::toInt($configured_size);
        if ($file_size_bytes > $configured_size_bytes) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Exclude private files from being indexed.
   *
   * Only happens if the module is configured to do so(default behaviour).
   *
   * @param object $file
   *   File object.
   * @param bool $excluded_private
   *   Boolean value whether exclude private file.
   *
   * @return bool
   *   TRUE if we should prevent current file from being indexed.
   */
  public function isPrivateFileAllowed($file, $excluded_private = TRUE) {
    // Know if private files are allowed to be indexed.
    $private_allowed = !$excluded_private;
    // Know if current file is private.
    $uri = $file->getFileUri();
    $file_is_private = FALSE;
    if (substr($uri, 0, 10) == 'private://') {
      $file_is_private = TRUE;
    }

    if (!$file_is_private) {
      return TRUE;
    }
    else {
      return $private_allowed;
    }
  }

}
