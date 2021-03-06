<?php

namespace Drupal\liiweb_legislation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;

/**
 * Provides a 'ViewTabsBlock' block.
 *
 * @Block(
 *  id = "view_tabs_block",
 *  admin_label = @Translation("Legislation View tabs block"),
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

    //Render legislation bk_legislation_browser
    $block_browser = views_embed_view('legislation', 'bk_legislation_browser');


    $alphas = range('A', 'Z');

        // dump($alphas);



    // dump($block_browser);
    if ($block_browser) {
      $build['#browser'] = $block_browser;

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'legislation', '=')
        ->condition('status', 1)
        ->sort('title', 'ASC');

      $legislation = $query->execute();

      // $legislation = $this::fetch();
      // if ($legislation) {
      //   $title = [];
      //   $items = [];
      //   $firstchar = [];

      //   foreach ($legislation as $revision_id => $nid) {
      //     $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      //     $node = $node_storage->load($nid);
      //     $title = $node->get('title')->value;
      //     // $titles = $this->sortAndIndexArray($title);

      //     $firstchar[] = strtoupper(substr($title, 0, 1));
      //     // dump($firstchar);
      //     $chars = array_intersect($alphas, $firstchar);

      //   }

      //   // dump($result);
      //   if ($chars) {
      //     foreach ($chars as $key => $char) {
      //       $items[] = [
      //         '#markup' => '<a href="#'.  $char .'">' . $char . '</a>',
      //         '#wrapper_attributes' => [
      //           'class'             => [strtolower($char), 'list-item',],
      //         ],
      //       ];
      //     }
      //   }

      //   $build['#glossary_filter'] = [
      //   '#theme'      => 'item_list',
      //   '#type'       => 'ul',
      //   '#attributes' => [
      //     'class'     => ['list-items', 'glossary-filter'],
      //   ],
      //   '#items'      => $items,
      //   ];
      // }
    }
    //Render legislation bk_legislation_subject
    $block_subject = views_embed_view('legislation', 'bk_legislation_subject');
    if ($block_subject) {
      $build['#subject'] = $block_subject;
    }

    //Render legislation bk_legislation_search
    $block_search = views_embed_view('index_search_legislation', 'bk_legislation_search');
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

