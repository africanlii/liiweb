<?php

namespace Drupal\liiweb_judgment\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;

/**
 * Provides a 'ViewTabsBlock' block.
 *
 * @Block(
 *  id = "view_judgment_tabs_block",
 *  admin_label = @Translation("judgment View tabs block"),
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

    //Render judgment bk_legislation_browser
    $block_browser = views_embed_view('judgment', 'bk_judgment_browser');

    // dump($block_browser);
    if ($block_browser) {
      $build['#browser'] = $block_browser;

//       $query = \Drupal::entityQuery('node')
//         ->condition('type', 'judgment', '=')
//         ->condition('status', 1)
//         ->sort('title', 'ASC');

//       $judgment = $query->execute();
// // dump($judgment);
//       // $judgment = $this::fetch();
//       if ($judgment) {
//         $title = [];
//         $items = [];
//         foreach ($judgment as $revision_id => $nid) {
//           $node_storage = \Drupal::entityTypeManager()->getStorage('node');
//           $node = $node_storage->load($nid);
//           $title[] = $node->get('title')->value;
//           $titles = $this->sortAndIndexArray($title);
//           }
//         // dump($titles);

//         if ($titles) {
//           foreach ($titles as $char => $title) {
//             //     // dump($nodes);
//             $items[] = [
//               '#markup' => '<a href="#'.  $char .'">' . $char . '</a>',
//               '#wrapper_attributes' => [
//                 'class'             => [strtolower($char), 'list-item',],
//               ],
//             ];
//           }
//         }

//         $build['#glossary_filter'] = [
//         '#theme'      => 'item_list',
//         '#type'       => 'ul',
//         '#attributes' => [
//           'class'     => ['list-items', 'glossary-filter'],
//         ],
//         '#items'      => $items,
//         ];
//       }
    }
    //Render judgment bk_legislation_subject
    $block_subject = views_embed_view('judgment', 'bk_judgment_subject');
    if ($block_subject) {
      $build['#subject'] = $block_subject;
    }

    //Render judgment bk_legislation_search
    $block_search = views_embed_view('index_search_judgment', 'bk_judgment_search');
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

