<?php

namespace Drupal\liiweb_judgement\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;

/**
 * Provides a 'ViewTabsBlock' block.
 *
 * @Block(
 *  id = "view_judgement_tabs_block",
 *  admin_label = @Translation("judgement View tabs block"),
 * )
 */
class ViewTabsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * render lislation view blocks (subject,search,borwser)
   */
  public function build() {
    $build = [];

    $build = [
      '#theme'  => 'view_tabs_block',
      '#subject' => [],
      '#search' => [],
      '#browser' => [],
      '#glossary_filter' => [],
    ];

    //Render judgement bk_legislation_browser
    $block_browser = views_embed_view('judgement', 'bk_judgement_browser');

    // dump($block_browser);
    if ($block_browser) {
      $build['#browser'] = $block_browser;

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'judgement', '=')
        ->condition('status', 1)
        ->sort('title', 'ASC');

      $judgement = $query->execute();
// dump($judgement);
      // $judgement = $this::fetch();
      if ($judgement) {
        $title = [];
        $items = [];
        foreach ($judgement as $revision_id => $nid) {
          $node_storage = \Drupal::entityTypeManager()->getStorage('node');
          $node = $node_storage->load($nid);
          $title[] = $node->get('title')->value;
          $titles = $this->sortAndIndexArray($title);
          }
        // dump($titles);

        if ($titles) {
          foreach ($titles as $char => $title) {
            //     // dump($nodes);
            $items[] = [
              '#markup' => '<a href="#'.  $char .'">' . $char . '</a>',
              '#wrapper_attributes' => [
                'class'             => [strtolower($char), 'list-item',],
              ],
            ];
          }
        }

        $build['#glossary_filter'] = [
        '#theme'      => 'item_list',
        '#type'       => 'ul',
        '#attributes' => [
          'class'     => ['list-items', 'glossary-filter'],
        ],
        '#items'      => $items,
        ];
      }
    }
    //Render judgement bk_legislation_subject
    $block_subject = views_embed_view('judgement', 'bk_judgement_subject');
    if ($block_subject) {
      $build['#subject'] = $block_subject;
    }

    //Render judgement bk_legislation_search
    $block_search = views_embed_view('index_search_judgement', 'bk_judgement_search');
    if ($block_search) {
      $build['#search'] = $block_search;
    }


    return $build;
  }

  public function sortAndIndexArray($aArray)
  {
    sort($aArray);
    foreach ($aArray as $sWord) {
      $aFinal[strtoupper(substr($sWord, 0, 1))][] = ucfirst($sWord);
    }
    ksort($aFinal);
    return $aFinal;
  }

}

