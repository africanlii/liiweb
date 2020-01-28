<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting given terms.
 */
class DeleteTermsForm extends FormBase {

  use MessengerTrait;

  /**
   * The current request.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * DeleteTermsForm constructor.
   *
   * @param \Drupal\taxonomy\TermStorage $termStorage
   *   Object with convenient methods to manage terms.
   */
  public function __construct(TermStorage $termStorage) {
    $this->termStorage = $termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = []) {
    if (empty($selected_terms)) {
      $form['info'] = [
        '#markup' => $this->t('Please select the terms you want to delete.'),
      ];
      return $form;
    }

    // Cache form state so that we keep the parents in the modal dialog.
    $form_state->setCached(TRUE);
    $form['voc'] = ['#type' => 'value', '#value' => $taxonomy_vocabulary];
    $form['selected_terms']['#tree'] = TRUE;

    $items = [];
    foreach ($selected_terms as $t) {
      $term = $this->termStorage->load($t);
      $items[] = $term->getName();
      $form['selected_terms'][$t] = ['#type' => 'value', '#value' => $t];
    }

    $form['terms'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Selected terms for deletion:'),
    ];

    $form['delete_orphans'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete children of selected terms, if there are any'),
    ];

    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue('selected_terms');
    $delete_orphans = $form_state->getValue('delete_orphans');

    $info = TaxonomyManagerHelper::deleteTerms($selected_terms, $delete_orphans);
    $this->messenger()->addMessage($this->t("Deleted terms: %deleted_term_names", ['%deleted_term_names' => implode(', ', $info['deleted_terms'])]));
    if (count($info['remaining_child_terms'])) {
      $this->messenger()->addMessage($this->t("Remaining child terms with different parents: %remaining_child_term_names", ['%remaining_child_term_names' => implode(', ', $info['remaining_child_terms'])]));
    }
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()]);

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager.delete_form';
  }

}
