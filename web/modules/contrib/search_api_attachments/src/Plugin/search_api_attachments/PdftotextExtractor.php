<?php

namespace Drupal\search_api_attachments\Plugin\search_api_attachments;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;
use Drupal\file\Entity\File;

/**
 * Provides pdftotext extractor.
 *
 * @SearchApiAttachmentsTextExtractor(
 *   id = "pdftotext_extractor",
 *   label = @Translation("Pdftotext Extractor"),
 *   description = @Translation("Adds Pdftotext extractor support."),
 * )
 */
class PdftotextExtractor extends TextExtractorPluginBase {

  /**
   * Extract file with Pdftotext command line tool.
   *
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   *
   * @return string
   *   The text extracted from the file.
   */
  public function extract(File $file) {
    if (in_array($file->getMimeType(), $this->getPdfMimeTypes())) {
      $output = '';
      $pdftotext_path = $this->configuration['pdftotext_path'];
      $filepath = $this->getRealpath($file->getFileUri());
      // UTF-8 multibyte characters will be stripped by escapeshellargs() for
      // the default C-locale.
      // So temporarily set the locale to UTF-8 so that the filepath remains
      // valid.
      $backup_locale = setlocale(LC_CTYPE, '0');
      setlocale(LC_CTYPE, 'en_US.UTF-8');
      // Pdftotext descriptions states that '-' as text-file will send text to
      // stdout.
      $cmd = escapeshellcmd($pdftotext_path) . ' ' . escapeshellarg($filepath) . ' -';
      // Restore the locale.
      setlocale(LC_CTYPE, $backup_locale);
      // Support UTF-8 commands.
      // @see http://www.php.net/manual/en/function.shell-exec.php#85095
      shell_exec("LANG=en_US.utf-8");
      $output = shell_exec($cmd);
      if (is_null($output)) {
        throw new \Exception('Pdftotext Exctractor is not available.');
      }
      return $output;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['pdftotext_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pdftotext binary'),
      '#description' => $this->t('Enter the name of pdftotext executable or the full path to the pdftotext binary. Example: "pdftotext" or "/usr/bin/pdftotext".'),
      '#default_value' => $this->configuration['pdftotext_path'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['text_extractor_config']);
    $pdftotext_path = $values['pdftotext_path'];

    $is_name = strpos($pdftotext_path, '/') === FALSE && strpos($pdftotext_path, '\\') === FALSE;
    if (!$is_name && !file_exists($pdftotext_path)) {
      $form_state->setError($form['text_extractor_config']['pdftotext_path'], $this->t('The file %path does not exist.', ['%path' => $pdftotext_path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['pdftotext_path'] = $form_state->getValue([
      'text_extractor_config',
      'pdftotext_path',
    ]);
    parent::submitConfigurationForm($form, $form_state);
  }

}
